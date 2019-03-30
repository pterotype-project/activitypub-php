<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UndoHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var CollectionsService
     */
    private $collectionsService;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
            OutboxActivityEvent::NAME => 'handleOutbox',
        );
    }

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
    }

    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Undo' ) {
            return;
        }
        $object = $this->getUndoObject( $activity );
        if ( ! ( $object && $object->hasField( 'type' ) ) ) {
            return;
        }
        $this->assertUndoIsValid( $activity, $object );
        switch ( $object['type'] ) {
            case 'Follow':
                $this->removeFromCollection( $object['object'], 'followers', $object['actor'] );
                break;
            case 'Like':
                $this->removeFromCollection( $object['object'], 'likes', $object['id'] );
                break;
            default:
                return;
        }
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Undo' ) {
            return;
        }
        $object = $this->getUndoObject( $activity );
        if ( ! ( $object && $object->hasField( 'type' ) ) ) {
            return;
        }
        $this->assertUndoIsValid( $activity, $object );
        switch ( $object['type'] ) {
            case 'Follow':
                $this->removeFromCollection( $object['actor'], 'following', $object['object'] );
                break;
            case 'Like':
                $this->removeFromCollection( $object['actor'], 'liked', $object['object'] );
                break;
            default:
                return;
        }
    }

    private function assertUndoIsValid( $activity, ActivityPubObject $undoObject )
    {
        if ( ! array_key_exists( 'actor', $activity ) ) {
            throw new AccessDeniedHttpException("You can't undo an activity you don't own");
        }
        $actorId = $activity['actor'];
        if ( is_array( $actorId ) && array_key_exists( 'id', $actorId ) ) {
            $actorId = $actorId['id'];
        }
        if ( ! is_string( $actorId ) ) {
            throw new AccessDeniedHttpException("You can't undo an activity you don't own");
        }
        $objectActor = $undoObject['actor'];
        if ( ! $objectActor ) {
            throw new AccessDeniedHttpException("You can't undo an activity you don't own");
        }
        if ( $actorId != $objectActor['id'] ) {
            throw new AccessDeniedHttpException("You can't undo an activity you don't own");
        }
    }

    private function removeFromCollection( $object, $collectionField, $itemId )
    {
        if ( ! ( $object && $object instanceof ActivityPubObject ) ) {
            return;
        }
        if ( ! $object->hasField( $collectionField ) ) {
            return;
        }
        $collection = $object[$collectionField];
        if ( ! ( $collection && $collection instanceof ActivityPubObject ) ) {
            return;
        }
        if ( ! $itemId ) {
            return;
        }
        if ( $itemId instanceof ActivityPubObject && $itemId->hasField( 'id' ) ) {
            $itemId = $itemId['id'];
        } else if ( is_array( $itemId ) && array_key_exists( 'id', $itemId ) ) {
            $itemId = $itemId['id'];
        }
        if ( ! is_string( $itemId ) ) {
            return;
        }
        $this->collectionsService->removeItem( $collection, $itemId );
    }

    /**
     * Gets the object of the undo activity as an ActivityPubObject
     * @param $activity
     * @return \ActivityPub\Entities\ActivityPubObject|null
     */
    private function getUndoObject( $activity )
    {
        $objectId = $activity['object'];
        if ( is_array( $objectId ) ) {
            if ( ! array_key_exists( 'id', $objectId ) ) {
                return null;
            }
            $objectId = $objectId['id'];
        }
        return $this->objectsService->dereference( $objectId );
    }
}