<?php /** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Objects;

use ActivityPub\Auth\AuthService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Utils\DateTimeProvider;
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
     * @return array
     */
    public function pageAndFilterCollection( Request $request,
                                             ActivityPubObject $collection )
    {
        if ( $request->query->has( 'offset' ) ) {
            return $this->getCollectionPage(
                $collection, $request, intval( $request->query->get( 'offset' ) ), $this->pageSize
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
            $collection, $request, 0, $this->pageSize
        );
        $colArr['first'] = $firstPage;
        return $colArr;
    }

    private function getCollectionPage( ActivityPubObject $collection,
                                        Request $request,
                                        $offset,
                                        $pageSize )
    {
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
        $idx = $offset;
        $count = 0;
        while ( $count < $pageSize ) {
            $item = $collectionItems->getFieldValue( $idx );
            if ( !$item ) {
                break;
            }
            if ( is_string( $item ) ) {
                $pageItems[] = $item;
                $count++;
            } else if ( $this->authService->isAuthorized( $request, $item ) ) {
                $pageItems[] = $item->asArray( 1 );
                $count++;
            }
            $idx++;
        }
        if ( $count === 0 ) {
            throw new NotFoundHttpException();
        }
        $page = array(
            '@context' => $this->contextProvider->getContext(),
            'id' => $collection['id'] . "?offset=$offset",
            'type' => $pageType,
            $itemsKey => $pageItems,
            'partOf' => $collection['id'],
        );
        // TODO set 'first' and 'last' on the page
        $nextIdx = $this->hasNextItem( $request, $collectionItems, $idx );
        if ( $nextIdx ) {
            $page['next'] = $collection['id'] . "?offset=$nextIdx";
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

    private function hasNextItem( Request $request, ActivityPubObject $collectionItems, $idx )
    {
        $next = $collectionItems->getFieldValue( $idx );
        while ( $next ) {
            if ( is_string( $next ) ||
                $this->authService->isAuthorized( $request, $next ) ) {
                return $idx;
            }
            $idx++;
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
}

