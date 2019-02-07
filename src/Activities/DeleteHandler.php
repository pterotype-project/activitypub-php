<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\ActivityEvent;
use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\DateTimeProvider;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DeleteHandler implements EventSubscriberInterface
{
    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleDelete',
            OutboxActivityEvent::NAME => 'handleDelete',
        );
    }

    public function __construct( DateTimeProvider $dateTimeProvider,
                                 ObjectsService $objectsService )
    {
        $this->dateTimeProvider = $dateTimeProvider;
        $this->objectsService = $objectsService;
    }

    public function handleDelete( ActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Delete' ) {
            return;
        }
        $objectId = $activity['object'];
        if ( ! is_string( $objectId ) ) {
            if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
                $objectId = $objectId['id'];
            } else {
                throw new BadRequestHttpException( 'Object must have an "id" field' );
            }
        }
        if ( ! $this->authorized( $event->getRequest(), $objectId ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        $tombstone = array(
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $objectId,
            'type' => 'Tombstone',
            'deleted' => $this->getNowTimestamp(),
        );
        $existing = $this->objectsService->dereference( $objectId );
        if ( $existing ) {
            $tombstone['formerType'] = $existing['type'];
        }
        $this->objectsService->replace( $objectId, $tombstone );
    }

    private function getNowTimestamp()
    {
        return $this->dateTimeProvider->getTime( 'activities.delete' )
            ->format( DateTime::ISO8601 );
    }

    public function authorized( Request $request,  $objectId )
    {
        if ( ! $request->attributes->has( 'actor' ) ) {
            return false;
        }
        $requestActor = $request->attributes->get( 'actor' );
        $object = $this->objectsService->dereference( $objectId );
        if ( ! $object || ! $object->hasField( 'attributedTo' ) ) {
            return false;
        }
        $attributedActorId = $object['attributedTo'];
        if ( ! is_string( $attributedActorId ) ) {
            $attributedActorId = $attributedActorId['id'];
        }
        return $requestActor['id'] === $attributedActorId;
    }
}

