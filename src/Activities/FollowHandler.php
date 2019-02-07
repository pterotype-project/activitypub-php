<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Objects\ContextProvider;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class FollowHandler implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $autoAccepts;

    /**
     * @var ContextProvider
     */
    private $contextProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
        );
    }

    public function __construct( $autoAccepts,
                                 ContextProvider $contextProvider )
    {
        $this->autoAccepts = $autoAccepts;
        $this->contextProvider = $contextProvider;
    }

    public function handleInbox( InboxActivityEvent $event,
                                 $eventName,
                                 EventDispatcher $eventDispatcher )
    {
        $activity = $event->getActivity();
        if ( ! $activity['type'] === 'Follow' ) {
            return;
        }
        if ( $this->autoAccepts ) {
            $localActor = $event->getActor();
            $objectId = $activity['object'];
            if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
                $objectId = $objectId['id'];
            }
            if ( $localActor['id'] !== $objectId ) {
                return;
            }
            $accept = array(
                '@context' => $this->contextProvider->getContext(),
                'type' => 'Accept',
                'actor' => $localActor['id'],
                'object' => $activity['id'],
            );
            $request = Request::create(
                $localActor['outbox'],
                Request::METHOD_POST,
                array(), array(), array(),
                array(
                    'HTTP_ACCEPT' => 'application/ld+json',
                    'CONTENT_TYPE' => 'application/json'
                ),
                json_encode( $accept )
            );
            $request->attributes->add( array(
                'actor' => $localActor,
                'follow' => $activity,
            ) );
            $outboxEvent = new OutboxActivityEvent( $accept, $localActor, $request );
            $eventDispatcher->dispatch( OutboxActivityEvent::NAME, $outboxEvent );
        }
    }
}

