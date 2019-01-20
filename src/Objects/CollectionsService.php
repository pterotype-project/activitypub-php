<?php
namespace ActivityPub\Objects;

use ActivityPub\Entities\ActivityPubObject;
use Symfony\Component\HttpFoundation\Request;

class CollectionsService
{
    const PAGE_SIZE = 20;

    /**
     * Returns an array representation of the $collection
     *
     * If the collection's size is greater than 30, return a PagedCollection instead,
     * and filter all items by the request's permissions
     */
    public function pageAndFilterCollection( Request $request,
                                             ActivityPubObject $collection )
    {
        // expected behavior:
        // - request with no 'offset' param returns the collection object,
        //   with the first page appended as with Pleroma
        // - request with an 'offset' param returns the collection page starting
        //   at that offset with the next PAGE_SIZE items
        if ( $request->query->has( 'offset' ) ) {
            // return a filtered collection page
        }
        // else return the collection itself with the first page
    }

    private function getCollectionPage( ActivityPubObject $collection,
                                        int $offset,
                                        int $pageSize )
    {
        $itemsKey = 'items';
        $pageType = 'CollectionPage';
        if ( $this->isOrdered( $collection ) ) {
            $itemsKey = 'orderedItems';
            $pageType = 'OrderedCollectionPage';
        }
        // Create and return the page as an array
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
