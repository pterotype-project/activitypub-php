<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UpdateHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( ObjectsService $objectsService )
    {
        $this->objectsService = $objectsService;
    }

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
        $object = $activity['object'];
        if ( !array_key_exists( 'id', $object ) ) {
            throw new BadRequestHttpException( 'Update object has no "id" field' );
        }
        if ( !$this->authorized( $event->getRequest(), $object ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        $this->objectsService->replace( $object['id'], $object );
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
        if ( !$request->attributes->has( 'actor' ) ) {
            return false;
        }
        if ( !array_key_exists( 'id', $object ) ) {
            return false;
        }
        $object = $this->objectsService->dereference( $object['id'] );
        if ( !$object->hasField( 'attributedTo' ) ) {
            return false;
        }
        $attributedActorId = $object['attributedTo'];
        if ( is_array( $attributedActorId ) &&
            array_key_exists( 'id', $attributedActorId ) ) {
            $attributedActorId = $attributedActorId['id'];
        }
        if ( !is_string( $attributedActorId ) ) {
            return false;
        }
        $requestActor = $request->attributes->get( 'actor' );
        return $requestActor['id'] === $attributedActorId;
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Update' ) {
            return;
        }
        $updateFields = $activity['object'];
        if ( !array_key_exists( 'id', $updateFields ) ) {
            throw new BadRequestHttpException( 'Update object has no "id" field' );
        }
        if ( !$this->authorized( $event->getRequest(), $updateFields ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        $updated = $this->objectsService->update( $updateFields['id'], $updateFields );
        $activity['object'] = $updated->asArray();
        $event->setActivity( $activity );
    }
}
