<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivityPersister implements EventSubscriberInterface
{
    /**
     * @var CollectionsService
     */
    private $collectionsService;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'persistActivityToInbox',
            OutboxActivityEvent::NAME => 'persistActivityToOutbox',
        );
    }

    public function __construct( CollectionsService $collectionsService,
                                 ObjectsService $objectsService )
    {
        $this->collectionsService = $collectionsService;
        $this->objectsService = $objectsService;
    }

    public function persistActivityToInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $receivingActor = $event->getReceivingActor();
        if ( $receivingActor->hasField( 'inbox' ) ) {
            $this->collectionsService->addItem( $receivingActor['inbox'], $activity );
        } else {
            $this->objectsService->persist( $activity );
        }
    }

    public function persistActivityToOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $receivingActor = $event->getReceivingActor();
        if ( $receivingActor->hasField( 'outbox' ) ) {
            $this->collectionsService->addItem( $receivingActor['outbox'], $activity );
        } else {
            $this->objectsService->persist( $activity );
        }
    }
}