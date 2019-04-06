<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeliveryHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInboxForwarding',
            OutboxActivityEvent::NAME => 'deliverActivity',
        );
    }

    public function __construct( ObjectsService $objectsService )
    {
        $this->objectsService = $objectsService;
    }

    public function handleInboxForwarding( InboxActivityEvent $event )
    {

    }

    public function deliverActivity( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $recipientFields = array( 'to', 'bto', 'cc', 'bcc', 'audience' );
        $inboxes = array();
        // TODO handle Links and Collections
        foreach ( $recipientFields as $field ) {
            if ( array_key_exists( $field, $activity ) ) {
                $recipients = $activity[$field];
                if ( ! is_array( $recipients ) ) {
                    $recipients = array( $recipients );
                }
                foreach ( $recipients as $recipient ) {
                    if ( is_array( $recipient ) && array_key_exists( 'id', $recipient ) ) {
                        $recipient = $recipient['id'];
                    }
                    if ( is_string( $recipient ) ) {
                        $recipientObj = $this->objectsService->dereference( $recipient );
                        if ( $recipientObj && $recipientObj->hasField( 'inbox' ) ) {
                            $inbox = $recipientObj['inbox'];
                            if ( $inbox instanceof ActivityPubObject && $inbox->hasField( 'id' ) ) {
                                $inbox = $inbox['id'];
                            }
                            if ( is_string( $inbox ) ) {
                                $inboxes[] = $inbox;
                            }
                        }
                    }
                }
            }
        }
        $inboxes = array_unique( $inboxes );
        $activityActor = $activity['actor'];
        if ( is_array( $activityActor ) && array_key_exists( 'id', $activityActor ) ) {
            $activityActor = $activityActor['id'];
        }
        if ( is_string( $activityActor ) ) {
            $inboxes = array_diff( $inboxes, array( $activityActor ) );
        }
        foreach ( array( 'bto', 'bcc' ) as $privateField ) {
            if ( array_key_exists( $privateField, $activity ) ) {
                unset( $activity[$privateField] );
            }
        }
        // deliver activity to all inboxes, signing the request and not blocking
    }
}