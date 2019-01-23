<?php
namespace ActivityPub\Controllers;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
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
     */
    public function handle( Request $request )
    {
        $uri = $this->getUriWithoutQuery( $request );
        $object = $this->objectsService->query( array( 'id' => $uri ) );
        if ( ! $object ) {
            throw new NotFoundHttpException;
        }
        $inboxField = $object->getReferencingField( 'inbox' );
        if ( $inboxField ) {
            $actorWithInbox = $inboxField->getObject();
            if ( ! $request->attributes->has( 'signed' ) ||
                 ! $this->authorized( $request, $actorWithInbox ) ) {
                throw new UnauthorizedHttpException();
            }
            $activity = json_decode( $request->getContent(), true );
            if ( ! $activity ) {
                throw new BadRequestHttpException();
            }
            $event = new InboxActivityEvent( $activity, $actorWithInbox, $request );
            $this->eventDispatcher->dispatch( InboxActivityEvent::NAME, $event );
            return;
        }
        $outboxField = $object->getReferencingField( 'outbox' );
        if ( $outboxField ) {
            $actorWithOutbox = $outboxField->getObject();
            if ( ! $this->authorized( $request, $actorWithOutbox ) ) {
                throw new UnauthorizedHttpException();
            }
            $activity = json_decode( $request->getContent(), true );
            if ( ! $activity ) {
                throw new BadRequestHttpException();
            }
            $event = new OutboxActivityEvent( $activity, $actorWithOutbox, $request );
            $this->eventDispatcher->dispatch( OutboxActivityEvent::NAME, $event );
            return;
        } 
        throw new MethodNotAllowedHttpException( array( Request::METHOD_GET ) );
    }

    private function authorized( Request $request, ActivityPubObject $activityActor )
    {
        if ( ! $request->attributes->has( 'actor' ) ) {
            return false;
        }
        $requestActor = $request->attributes->get( 'actor' );
        if ( $requestActor['id'] !== $activityActor['id'] ) {
            return false;
        }
        return true;
    }

    private function objectWithField( string $name, string $value )
    {
        $results = $this->objectsService->query( array( $name => $value ) );
        if ( count( $results ) === 0 ) {
            return false;
        }
        return $results[0];
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
}
?>
