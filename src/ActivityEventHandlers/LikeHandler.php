<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LikeHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var CollectionsService
     */
    private $collectionsService;

    /**
     * @var ContextProvider
     */
    private $contextProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
            OutboxActivityEvent::NAME => 'handleOutbox',
        );
    }

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService,
                                 ContextProvider $contextProvider )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
        $this->contextProvider = $contextProvider;
    }

    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Like' ) {
            return;
        }
        $objectId = $activity['object'];
        if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
            $objectId = $objectId['id'];
        }
        if ( ! is_string( $objectId ) ) {
            throw new BadRequestHttpException('Invalid object');
        }
        $object = $this->objectsService->dereference( $objectId );
        if ( ! $object->hasField( 'likes' ) ) {
            $object = $this->addCollectionToObject( $object, 'likes' );
        }
        $likes = $object['likes'];
        $this->collectionsService->addItem( $likes, $activity );
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Like' ) {
            return;
        }
        $object = $activity['object'];
        $actor = $event->getActor();
        if ( ! $actor->hasField( 'liked' ) ) {
            $actor = $this->addCollectionToObject( $actor, 'liked' );
        }
        $liked = $actor['liked'];
        $this->collectionsService->addItem( $liked, $object );
    }

    private function addCollectionToObject( ActivityPubObject $object, $collectionName )
    {
        $updatedObject = $object->asArray();
        $updatedObject[$collectionName] = array(
            '@context' => $this->contextProvider->getContext(),
            'id' => rtrim( $updatedObject['id'], '/' ) . '/' . $collectionName,
            'type' => 'Collection',
            'items' => array(),
        );
        return $this->objectsService->update( $object['id'], $updatedObject );
    }
}