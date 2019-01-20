<?php
namespace ActivityPub\Objects;

use ActivityPub\Auth\AuthService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ContextProvider;
use Symfony\Component\HttpFoundation\Request;

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

    public function __construct( int $pageSize, AuthService $authService,
                                 ContextProvider $contextProvider )
    {
        $this->pageSize = $pageSize;
        $this->authService = $authService;
        $this->contextProvider = $contextProvider;
    }

    /**
     * Returns an array representation of the $collection
     *
     * If the collection's size is greater than 30, return a PagedCollection instead,
     * and filter all items by the request's permissions
     */
    public function pageAndFilterCollection( Request $request,
                                             ActivityPubObject $collection )
    {
        if ( $request->query->has( 'offset' ) ) {
            return $this->getCollectionPage(
                $collection, $request, $request->query->get( 'offset' ), $this->pageSize
            );
        }
        $colArr = array();
        foreach ( $collection->getFields() as $field ) {
            if ( ! in_array( $field->getName(), array( 'items', 'orderedItems' ) ) ) {
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
                                        int $offset,
                                        int $pageSize )
    {
        $itemsKey = 'items';
        $pageType = 'CollectionPage';
        $isOrdered = $this->isOrdered( $collection );
        if ( $isOrdered ) {
            $itemsKey = 'orderedItems';
            $pageType = 'OrderedCollectionPage';
        }
        if ( ! $collection->hasField( $itemsKey ) ) {
            throw new InvalidArgumentException(
                "Collection does not have an \"$field\" key"
            );
        }
        $collectionItems = $collection->getFieldValue( $itemsKey );
        $pageItems = array();
        $idx = $offset;
        $count = 0;
        while ( $count < $pageSize ) {
            $item = $collectionItems->getFieldValue( $idx );
            if ( ! $item ) {
                break;
            }
            if ( is_string( $item ) ) {
                $pageItems[] = $item;
                $count++;
            } else if ( $this->authService->requestAuthorizedToView( $request, $item ) ) {
                $pageItems[] = $item->asArray( 1 );
                $count++;
            }
            $idx++;
        }
        $page = array(
            '@context' => $this->contextProvider->getContext(),
            'id' => $collection['id'] . "?offset=$offset",
            'type' => $pageType,
            $itemsKey => $pageItems,
            'partOf' => $collection['id'],
        );
        $nextIdx = $this->hasNextItem( $request, $collectionItems, $idx );
        if ( $nextIdx ) {
            $page['next'] = $collection['id'] . "?offset=$nextIdx";
        }
        if ( $isOrdered ) {
            $page['startIndex'] = $offset;
        }
        return $page;
    }

    private function hasNextItem( $request, $collectionItems, $idx )
    {
        $next = $collectionItems->getFieldValue( $idx );
        while ( $next ) {
            if ( is_string( $next ) ||
                 $this->authService->requestAuthorizedToView( $request, $next ) ) {
                return $idx;
            }
            $idx++;
            $next = $collectionsItems->getFieldValue( $idx );
        }
        return false;
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
}
?>
