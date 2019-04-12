<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionIterator;
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
                        $inboxes = array_merge( $inboxes, $this->resolveRecipient( $recipientObj ) );
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

    /**
     * Given an ActivityPubObject to deliver to, returns an array of inbox URLs
     * @param ActivityPubObject $recipient
     * @return array
     */
    private function resolveRecipient( ActivityPubObject $recipient )
    {
        if ( $recipient && $recipient->hasField( 'inbox' ) ) {
            $inbox = $recipient['inbox'];
            if ( $inbox instanceof ActivityPubObject && $inbox->hasField( 'id' ) ) {
                $inbox = $inbox['id'];
            }
            if ( is_string( $inbox ) ) {
                return array( $inbox );
            }
        } else if (
            $recipient &&
            $recipient->hasField( 'type' ) &&
            in_array( $recipient['type'], array( 'Collection', 'OrderedCollection' ) )
        ) {
            $inboxes = array();
            foreach ( CollectionIterator::iterateCollection( $recipient ) as $item ) {
                if ( $item instanceof ActivityPubObject ) {
                    $inboxes = array_unique( array_merge( $inboxes, $this->resolveRecipient( $item ) ) );
                }
            }
            return $inboxes;
        }
        return array();
    }
}