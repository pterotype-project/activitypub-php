<?php
namespace ActivityPub\Activities;

use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AcceptHandler implements EventSubscriberInterface
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
        if ( $activity['type'] !== 'Accept' ) {
            return;
        }
        // add to following collection
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Accept' ) {
            return;
        }
        $request = $event->getRequest();
        // either there is a 'follow' key on the request,
        // in which case this is an auto-accept dispatched from
        // the FollowHandler so the Follow won't be in the database yet,
        // or there isn't, in which case this is an ordinary Accept
        // sent by a client and the Follow is in the database
        $follow = $request->attributes->get( 'follow' );
        if ( ! $follow ) {
            $followId = $activity['object'];
            if ( is_array( $followId ) && array_key_exists( 'id', $followId ) ) {
                $followId = $followId['id'];
            }
            if ( ! is_string( $followId ) ) {
                return;
            }
            $follow = $this->objectsService->dereference( $followId )->asArray( -1 );
        }
        if ( ! $follow || ! array_key_exists( 'object', $follow ) ) {
            return;
        }
        $followObjectId = $follow['object'];
        if ( is_array( $followObjectId ) && array_key_exists( 'id', $followObjectId ) ) {
            $followObjectId = $followObjectId['id'];
        }
        $actor = $event->getActor();
        if ( $followObjectId !== $actor['id'] ) {
            return;
        }
        $this->collectionsService->addItem( $actor['followers'], $activity['actor'] );
    }
}

