<?php /** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Objects;

use ActivityPub\Auth\AuthService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Utils\DateTimeProvider;
use Closure;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CollectionsService
{
    /**
     * @var int
     */
    private $pageSize;

    /**
     * @var AuthService
     */
    private $authService;

    /**
     * @var ContextProvider
     */
    private $contextProvider;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( $pageSize,
                                 AuthService $authService,
                                 ContextProvider $contextProvider,
                                 Client $httpClient,
                                 DateTimeProvider $dateTimeProvider,
                                 EntityManager $entityManager,
                                 ObjectsService $objectsService )
    {
        $this->pageSize = $pageSize;
        $this->authService = $authService;
        $this->contextProvider = $contextProvider;
        $this->httpClient = $httpClient;
        $this->dateTimeProvider = $dateTimeProvider;
        $this->entityManager = $entityManager;
        $this->objectsService = $objectsService;
    }

    /**
     * Returns an array representation of the $collection
     *
     * Returns the collection paged and filtered by the request's authorization status
     * @param Request $request
     * @param ActivityPubObject $collection
     * @param Closure $filterFunc The function to filter by. Should be a closure that
     *                            takes an ActivityPubObject and returns a boolean, where
     *                            true means keep the item and false means filter it out
     * @return array
     */
    public function pageAndFilterCollection( Request $request,
                                             ActivityPubObject $collection,
                                             Closure $filterFunc )
    {
        $sort = 'desc';
        if ( $request->query->has( 'sort' ) && $request->query->get( 'sort' ) == 'asc' ) {
            $sort = 'asc';
        }
        if ( $request->query->has( 'offset' ) ) {
            return $this->getCollectionPage(
                $collection,
                $request,
                intval( $request->query->get( 'offset' ) ),
                $this->pageSize,
                $filterFunc,
                $sort
            );
        }
        $colArr = array();
        foreach ( $collection->getFields() as $field ) {
            if ( !in_array( $field->getName(), array( 'items', 'orderedItems' ) ) ) {
                if ( $field->hasValue() ) {
                    $colArr[$field->getName()] = $field->getValue();
                } else {
                    $colArr[$field->getName()] = $field->getTargetObject()->asArray( 1 );
                }
            }
        }
        $firstPage = $this->getCollectionPage(
            $collection, $request, 0, $this->pageSize, $filterFunc, $sort
        );
        $colArr['first'] = $firstPage;
        return $colArr;
    }

    private function getCollectionPage( ActivityPubObject $collection,
                                        Request $request,
                                        $offset,
                                        $pageSize,
                                        Closure $filterFunc,
                                        $sort )
    {
        $asc = $sort == 'asc';
        $itemsKey = 'items';
        $pageType = 'CollectionPage';
        $isOrdered = $this->isOrdered( $collection );
        if ( $isOrdered ) {
            $itemsKey = 'orderedItems';
            $pageType = 'OrderedCollectionPage';
        }
        if ( !$collection->hasField( $itemsKey ) ) {
            throw new InvalidArgumentException(
                "Collection does not have an \"$itemsKey\" key"
            );
        }
        $collectionItems = $collection->getFieldValue( $itemsKey );
        $pageItems = array();
        if ( $asc ) {
            $idx = $offset;
        } else {
            $idx = $this->getCollectionSize( $collection ) - $offset - 1;
        }
        $count = 0;
        while ( $count < $pageSize ) {
            $item = $collectionItems->getFieldValue( $idx );
            if ( !$item ) {
                break;
            }
            if ( is_string( $item ) ) {
                $pageItems[] = $item;
                $count++;
            } else if ( call_user_func( $filterFunc, $item ) ) {
                $pageItems[] = $item->asArray( 1 );
                $count++;
            }
            if ( $asc ) {
                $idx++;
            } else {
                $idx--;
            }
        }
        if ( $count === 0 ) {
            throw new NotFoundHttpException();
        }
        $page = array(
            '@context' => $this->contextProvider->getContext(),
            'id' => $collection['id'] . "?offset=$offset&sort=$sort",
            'type' => $pageType,
            $itemsKey => $pageItems,
            'partOf' => $collection['id'],
        );
        // TODO set 'first' and 'last' on the page
        $nextIdx = $this->hasNextItem( $request, $collectionItems, $idx, $sort );
        if ( is_numeric( $nextIdx ) ) {
            if ( ! $asc ) {
                $nextIdx = $this->getCollectionSize( $collection ) - $nextIdx - 1;
            }
            $page['next'] = $collection['id'] . "?offset=$nextIdx&sort=$sort";
        }
        if ( $isOrdered ) {
            $page['startIndex'] = $offset;
        }
        return $page;
    }

    private function isOrdered( ActivityPubObject $collection )
    {
        if ( $collection->hasField( 'type' ) &&
            $collection['type'] === 'OrderedCollection' ) {
            return true;
        } else if ( $collection->hasField( 'type' ) &&
            $collection['type'] === 'Collection' ) {
            return false;
        } else {
            throw new InvalidArgumentException( 'Not a collection' );
        }
    }

    private function hasNextItem( Request $request, ActivityPubObject $collectionItems, $idx, $sort )
    {
        $asc = $sort == 'asc';
        $next = $collectionItems->getFieldValue( $idx );
        while ( $next ) {
            if ( is_string( $next ) ||
                $this->authService->isAuthorized( $request, $next ) ) {
                return $idx;
            }
            if ( $asc ) {
                $idx++;
            } else {
                $idx--;
            }
            $next = $collectionItems->getFieldValue( $idx );
        }
        return false;
    }

    /**
     * Given a collection as an array, normalize the collection by collapsing
     * collection pages into a single `items` or `orderedItems` array
     *
     * @param array $collection The collection to normalize
     * @return array The normalized collection
     */
    public function normalizeCollection( array $collection )
    {
        if ( $collection['type'] !== 'Collection' &&
            $collection['type'] !== 'OrderedCollection' ) {
            return $collection;
        }
        if ( !array_key_exists( 'first', $collection ) ) {
            return $collection;
        }
        $first = $collection['first'];
        if ( is_string( $first ) ) {
            $first = $this->fetchPage( $first );
            if ( !$first ) {
                throw new BadRequestHttpException(
                    "Unable to retrieve collection page '$first'"
                );
            }
        }
        $items = $this->getPageItems( $collection['first'] );
        $itemsField = $collection['type'] === 'Collection' ? 'items' : 'orderedItems';
        $collection[$itemsField] = $items;
        unset( $collection['first'] );
        if ( array_key_exists( 'last', $collection ) ) {
            unset( $collection['last'] );
        }
        return $collection;
    }

    private function fetchPage( $pageId )
    {
        $request = new Psr7Request( 'GET', $pageId, array(
            'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
        ) );
        $response = $this->httpClient->send( $request );
        if ( $response->getStatusCode() !== 200 || empty( $response->getBody() ) ) {
            return null;
        }
        return json_decode( $response->getBody(), true );
    }

    private function getPageItems( array $collectionPage )
    {
        $items = array();
        if ( array_key_exists( 'items', $collectionPage ) ) {
            $items = array_merge( $items, $collectionPage['items'] );
        } else if ( array_key_exists( 'orderedItems', $collectionPage ) ) {
            $items = array_merge( $items, $collectionPage['orderedItems'] );
        }
        if ( array_key_exists( 'next', $collectionPage ) ) {
            $nextPage = $collectionPage['next'];
            if ( is_string( $nextPage ) ) {
                $nextPage = $this->fetchPage( $nextPage );
            }
            if ( $nextPage ) {
                $items = array_merge( $items, $this->getPageItems( $nextPage ) );
            }
        }
        return $items;
    }

    /**
     * Adds $item to $collection
     *
     * @param ActivityPubObject $collection
     * @param array|string $item
     */
    public function addItem( ActivityPubObject $collection, $item )
    {
        if ( ! in_array( $collection['type'], array( 'Collection', 'OrderedCollection') ) ) {
            return;
        }
        if ( $collection['type'] === 'Collection' ) {
            $itemsFieldName = 'items';
        } else if ( $collection['type'] === 'OrderedCollection' ) {
            $itemsFieldName = 'orderedItems';
        }
        if ( !$collection->hasField( $itemsFieldName ) ) {
            $items = new ActivityPubObject(
                $this->dateTimeProvider->getTime( 'collections-service.add' )
            );
            $itemsField = Field::withObject(
                $collection, $itemsFieldName, $items, $this->dateTimeProvider->getTime( 'collections-service.add' )
            );
            $this->entityManager->persist( $itemsField );
            $this->entityManager->persist( $items );
            $this->entityManager->persist( $collection );
        } else {
            $items = $collection[$itemsFieldName];
        }
        if ( !$items instanceof ActivityPubObject ) {
            throw new Exception( 'Attempted to add an item to a collection with a non-object items field' );
        }
        if ( $collection->hasField( 'totalItems' ) && is_numeric( $collection['totalItems'] ) ) {
            // This will break if some other server puts in an incorrect value for totalItems
            $itemCount = intval( $collection['totalItems'] );
        } else {
            // This is making the assumption that $items *only* contains numeric fields (i.e., it is an array)
            // Also, it's O(n) on the size of the collection
            $itemCount = count( $items->getFields() );
        }
        if ( is_array( $item ) ) {
            $item = $this->objectsService->persist( $item, 'collections-service.add' );
            $newItemField = Field::withObject(
                $items, $itemCount, $item, $this->dateTimeProvider->getTime( 'collections-service.add' )
            );
        } else if ( is_string( $item ) ) {
            $newItemField = Field::withValue(
                $items, $itemCount, $item, $this->dateTimeProvider->getTime( 'collections-service.add' )
            );
        }
        $this->entityManager->persist( $newItemField );
        $this->entityManager->persist( $items );
        $this->entityManager->persist( $collection );
        if ( $collection->hasField( 'totalItems' ) && is_numeric( $collection['totalItems'] ) ) {
            $totalItemsField = $collection->getField( 'totalItems' );
        } else {
            $totalItemsField = $collection->getField( 'totalItems' );
            if ( !$totalItemsField ) {
                $totalItemsField = Field::withValue(
                    $collection, 'totalItems', strval( $itemCount ), $this->dateTimeProvider->getTime( 'collections-service.add' )
                );
            }

        }
        $currentCount = intval( $totalItemsField->getValue() );
        $totalItemsField->setValue(
            strval( $currentCount + 1 ), $this->dateTimeProvider->getTime( 'collections-service.add' )
        );
        $this->entityManager->persist( $totalItemsField );
        $this->entityManager->persist( $collection );
        $this->entityManager->flush();
    }

    /**
     * Remove the item with id $itemId from $collection, if such an item is present in the collection
     *
     * This is O(n) with the size of the collection.
     *
     * @param ActivityPubObject $collection
     * @param string $itemId
     */
    public function removeItem( ActivityPubObject $collection, $itemId )
    {
        if ( ! in_array( $collection['type'], array( 'Collection', 'OrderedCollection' ) ) ) {
            return;
        }
        if ( $collection['type'] === 'Collection' ) {
            $itemsFieldName = 'items';
        } else if ( $collection['type'] === 'OrderedCollection' ) {
            $itemsFieldName = 'orderedItems';
        }
        if ( ! $collection->hasField( $itemsFieldName ) ) {
            return;
        }
        $itemsObj = $collection[$itemsFieldName];
        foreach ( $itemsObj->getFields() as $arrayField ) {
            if ( $arrayField->hasTargetObject() &&
                $arrayField->getTargetObject()->getFieldValue( 'id' ) === $itemId ) {
                $foundItemField = $arrayField;
                $foundItemIndex = intval( $arrayField->getName() );
                break;
            }
        }
        if ( ! isset( $foundItemField ) ) {
            return;
        }
        $itemsObj->removeField(
            $foundItemField, $this->dateTimeProvider->getTime( 'collections-service.remove' )
        );
        $this->entityManager->persist( $itemsObj );
        $this->entityManager->remove( $foundItemField );
        while ( $itemsObj->hasField( $foundItemIndex + 1) ) {
            $nextItemField = $itemsObj->getField( $foundItemIndex + 1 );
            $nextItemField->setName(
                $foundItemIndex, $this->dateTimeProvider->getTime( 'collections-service.remove' )
            );
            $this->entityManager->persist( $nextItemField );
            $foundItemIndex = $foundItemIndex + 1;
        }
        $collection->setLastUpdated( $this->dateTimeProvider->getTime( 'collections-service.remove' ) );
        $this->entityManager->persist( $collection );
        $this->entityManager->flush();
    }

    public function getCollectionSize( ActivityPubObject &$collection )
    {
        if ( $collection->hasField( 'totalItems' ) && is_numeric( $collection['totalItems'] ) ) {
            return intval( $collection['totalItems'] );
        } else {
            $itemsField = 'items';
            if ( $collection->hasField( 'type' ) && $collection['type'] == 'OrderedCollection' ) {
                $itemsField = 'orderedItems';
            }
            if ( ! ( $collection->hasField( $itemsField ) && $collection[$itemsField] instanceof ActivityPubObject ) ) {
                return 0;
            }
            $items = $collection[$itemsField];
            $count = 0;
            $idx = 0;
            $currentItem = $items[$idx];
            while ( $currentItem ) {
                $count++;
                $idx++;
                $currentItem = $items[$idx];
            }
            $collection = $this->objectsService->update( $collection['id'], array( 'totalItems' => strval( $count ) ) );
            return $count;
        }
    }
}

