<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @var IdProvider
     */
    private $idProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'persistActivityToInbox',
            OutboxActivityEvent::NAME => 'persistActivityToOutbox',
        );
    }

    public function __construct( CollectionsService $collectionsService,
                                 ObjectsService $objectsService,
                                 IdProvider $idProvider )
    {
        $this->collectionsService = $collectionsService;
        $this->objectsService = $objectsService;
        $this->idProvider = $idProvider;
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
        $event->setResponse( new Response( 'Activity accepted', Response::HTTP_OK ) );
    }

    public function persistActivityToOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( ! array_key_exists( 'id', $activity ) ) {
            $activity['id'] = $this->idProvider->getId( $event->getRequest(), "activities" );
        }
        $receivingActor = $event->getReceivingActor();
        if ( $receivingActor->hasField( 'outbox' ) ) {
            $this->collectionsService->addItem( $receivingActor['outbox'], $activity );
        } else {
            $this->objectsService->persist( $activity );
        }
        $event->setResponse( new Response(
            'Activity accepted', Response::HTTP_CREATED, array( 'Location' => $activity['id'] )
        ) );
    }
}