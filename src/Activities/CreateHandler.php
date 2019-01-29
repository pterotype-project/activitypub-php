<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateHandler implements EventSubscriberInterface
{
    private $objectsService;
    private $idProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
            OutboxActivityEvent::NAME => 'handleOutbox',
        );
    }

    public function __construct( ObjectsService $objectsService,
                                 IdProvider $idProvider )
    {
        $this->objectsService = $objectsService;
        $this->idProvider = $idProvider;
    }
    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Create' ) {
            return;
        }
        // normalize an incoming collection
        // persist the object
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Create' ) {
            return;
        }
        $object = $activity['object'];
        if ( ! array_key_exists( 'id', $object ) ) {
            $object['id'] = $this->idProvider->getId(
                $event->getRequest(),
                strtolower( $object['type'] )
            );
        }
        $object['attributedTo'] = $this->getActorId( $activity );
        $object = $this->copyFields(
            array( 'to', 'cc', 'audience' ), $activity, $object
        );
        $activity = $this->copyFields(
            array( 'to', 'bto', 'cc', 'bcc', 'audience' ), $object, $activity
        );
        $activity['object'] = $object;
        // normalize an incoming collection
        // persist the object
    }

    private function getActorId( array $activity )
    {
        $actor = $activity['actor'];
        if ( is_string( $actor ) ) {
            return $actor;
        } else {
            return $actor['id'];
        }
    }

    private function copyFields( array $fields, array $sourceObj, array $targetObj )
    {
        foreach( $fields as $field ) {
            if ( ! array_key_exists( $field, $sourceObj ) ) {
                continue;
            }
            if ( array_key_exists( $field, $targetObj ) &&
                 $sourceObj[$field] === $targetObj[$field] ) {
                continue;
            } else if ( ! array_key_exists( $field, $targetObj ) ) {
                $targetObj[$field] = $sourceObj[$field];
            } else if ( is_array( $sourceObj[$field] ) &&
                        is_array( $targetObject[$field] ) ) {
                $targetObj[$field] = array_unique(
                    array_merge( $sourceObj[$field], $targetObj[$field] )
                );
            } else if ( is_array( $sourceObj[$field] ) &&
                        ! is_array( $targetObj[$field] ) ) {
                $targetObj[$field] = array( $targetObj[$field] );
                $targetObj[$field] = array_unique(
                    array_merge( $sourceObj[$field], $targetObj[$field] )
                );
            } else if ( ! is_array( $sourceObj[$field] ) &&
                        is_array( $targetObj[$field] ) ) {
                $targetObj[$field][] = $sourceObj[$field];
            } else if ( ! is_array( $sourceObj[$field] ) &&
                        ! is_array( $targetObj[$field] ) ) {
                $targetObj[$field] = array( $targetObj[$field] );
                $targetObj[$field][] = $sourceObj[$field];
            }
        }
        return $targetObj;
    }
}
?>
