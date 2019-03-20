<?php

namespace ActivityPub\Controllers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * The PostController is responsible for handling incoming ActivityPub POST requests
 */
class PostController
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( EventDispatcher $eventDispatcher,
                                 ObjectsService $objectsService )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->objectsService = $objectsService;
    }

    /**
     * Handles an incoming POST request
     *
     * Either dispatches an inbox/outbox activity event or throws the appropriate
     * HTTP error.
     * @param Request $request The request
     * @return Response
     */
    public function handle( Request $request )
    {
        $uri = $this->getUriWithoutQuery( $request );
        $results = $this->objectsService->query( array( 'id' => $uri ) );
        if ( count( $results ) === 0 ) {
            throw new NotFoundHttpException;
        }
        $object = $results[0];
        // TODO this assumes that every actor has a unique inbox URL
        // and will break if multiple actors have the same inbox
        // TODO also handle sharedInbox here
        $inboxField = $object->getReferencingField( 'inbox' );
        if ( $inboxField ) {
            $activity = json_decode( $request->getContent(), true );
            if ( !$activity || !array_key_exists( 'actor', $activity ) ) {
                throw new BadRequestHttpException();
            }
            $activityActor = $this->getActivityActor( $activity );
            if ( !$activityActor ) {
                throw new BadRequestHttpException();
            }
            if ( !$request->attributes->has( 'signed' ) ||
                !$this->authorized( $request, $activityActor ) ) {
                throw new UnauthorizedHttpException(
                    'Signature realm="ActivityPub",headers="(request-target) host date"'
                );
            }
            $actorWithInbox = $inboxField->getObject();
            $event = new InboxActivityEvent( $activity, $actorWithInbox, $request );
            $this->eventDispatcher->dispatch( InboxActivityEvent::NAME, $event );
            return $event->getResponse();
        }
        // TODO this assumes that every actor has a unique outbox URL
        // and will break if multiple actors have the same outbox
        $outboxField = $object->getReferencingField( 'outbox' );
        if ( $outboxField ) {
            $actorWithOutbox = $outboxField->getObject();
            if ( !$this->authorized( $request, $actorWithOutbox ) ) {
                throw new UnauthorizedHttpException(
                    'Signature realm="ActivityPub",headers="(request-target) host date"'
                );
            }
            $activity = json_decode( $request->getContent(), true );
            if ( !$activity ) {
                throw new BadRequestHttpException();
            }
            $event = new OutboxActivityEvent( $activity, $actorWithOutbox, $request );
            $this->eventDispatcher->dispatch( OutboxActivityEvent::NAME, $event );
            return $event->getResponse();
        }
        throw new MethodNotAllowedHttpException( array( Request::METHOD_GET ) );
    }

    private function getUriWithoutQuery( Request $request )
    {
        $uri = $request->getUri();
        $queryPos = strpos( $uri, '?' );
        if ( $queryPos !== false ) {
            $uri = substr( $uri, 0, $queryPos );
        }
        return $uri;
    }

    private function getActivityActor( array $activity )
    {
        $actor = $activity['actor'];
        if ( is_array( $actor ) && array_key_exists( 'id', $actor ) ) {
            return $this->objectsService->dereference( $actor['id'] );
        } else if ( is_string( $actor ) ) {
            return $this->objectsService->dereference( $actor );
        }
        return null;
    }

    private function authorized( Request $request, ActivityPubObject $activityActor )
    {
        if ( !$request->attributes->has( 'actor' ) ) {
            return false;
        }
        $requestActor = $request->attributes->get( 'actor' );
        if ( $requestActor['id'] !== $activityActor['id'] ) {
            return false;
        }
        return true;
    }
}

