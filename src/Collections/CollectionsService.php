<?php
namespace ActivityPub\Collections;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ObjectsService;

class CollectionsService
{
    /**
     * Creates a new collection - ordered/unordered and paged/unpaged
     *
     * @return ActivityPubObject The created Collection object
     */
    public function createCollection()
    {
        
    }

    /**
     * Adds the object to the collection
     *
     * This method handles ordered/unordered and paged/unpaged collections.
     * @param string $collectionId The id of collection
     * @param ActivityPubObject $object The object to add to the collection
     */
    public function addToCollection( string $collectionId, ActivityPubObject $object )
    {
        
    }

    /**
     * Removes the object from the collection
     *
     * This method handles ordered/unordered and paged/unpaged collections.
     * @param string $collectionId The id of the collection
     * @param string $objectId The id of the object to remove
     */
    public function removeFromCollection( string $collectionId, string $objectId )
    {
        
    }

    /**
     * Deletes the collection by replacing it with a Tombstone object
     *
     * None of the items in the collection will be deleted; however, if it is
     *   a PagedCollection then all the collection page objects will be deleted.
     * @param string $collectionId The id of the collection
     * @return ActivityPubObject The Tombstone that the collection was replaced with
     */
    public function deleteCollection( string $collectionId )
    {
        
    }
}
?>
