<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
            OutboxActivityEvent::NAME => 'handleOutbox',
        );
    }

    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Update' ) {
            return;
        }
        // make sure the request is authorized to update the object
        // replace the object with $activity->object
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Update' ) {
            return;
        }
        $updateFields = $activity['object'];
        if ( ! $this->authorized( $event->getRequest(), $updateFields ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        // make sure the request is authorized to update the object
        // replace the specified fields in the object with their updated values
        // set the $activity[object] to be the fully-updated object for delivery
    }

    /**
     * Returns true if $request is authorized to update $object
     *
     * @param Request $request The current request
     * @param array $object The object
     * @return bool
     */
    private function authorized( Request $request, array $object )
    {
        if ( ! $request->attributes->has( 'actor' ) ) {
            return false;
        }
        if ( ! array_key_exists( 'attributedTo', $object ) ) {
            return false;
        }
        $attributedActorId = $object['attributedTo'];
        if ( is_array( $attributedActorId ) &&
             array_key_exists( 'id', $attributedActorId ) ) {
            $attributedActorId = $attributedActorId['id'];
        }
        if ( ! is_string( $attributedActorId ) ) {
            return false;
        }
        $requestActor = $request->attributes->get( 'actor' );
        return $requestActor['id'] === $attributedActorId;
    }
}
