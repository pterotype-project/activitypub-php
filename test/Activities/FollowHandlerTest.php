<?php

namespace ActivityPub\Test\Activities;

use ActivityPub\ActivityEventHandlers\FollowHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class FollowHandlerTest extends APTestCase
{
    public function testFollowHandler()
    {
        $eventDispatcher = new EventDispatcher();
        $contextProvider = new ContextProvider();
        $followHandler = new FollowHandler( true, $contextProvider );
        $eventDispatcher->addSubscriber( $followHandler );
        $outboxDispatched = false;
        $actor = TestActivityPubObject::fromArray( array(
            'id' => 'https://example.com/actor/1',
            'outbox' => 'https://example.com/actor/1/outbox',
        ) );
        $follow = array(
            'id' => 'https://elsewhere.com/activities/1',
            'type' => 'Follow',
            'object' => 'https://example.com/actor/1',
        );
        $eventDispatcher->addListener( OutboxActivityEvent::NAME, function ( $event, $name )
        use ( &$outboxDispatched, $actor, $follow ) {
            $this->assertEquals( OutboxActivityEvent::NAME, $name );
            $outboxDispatched = true;
            $accept = array(
                '@context' => ContextProvider::getDefaultContext(),
                'type' => 'Accept',
                'actor' => 'https://example.com/actor/1',
                'object' => 'https://elsewhere.com/activities/1',
            );
            $expectedRequest = Request::create(
                'https://example.com/actor/1/outbox',
                Request::METHOD_POST,
                array(), array(), array(),
                array(
                    'HTTP_ACCEPT' => 'application/ld+json',
                    'CONTENT_TYPE' => 'application/json',
                ),
                json_encode( $accept )
            );
            $expectedRequest->attributes->add( array(
                'actor' => $actor,
                'follow' => $follow,
            ) );
            $this->assertEquals(
                new OutboxActivityEvent( $accept, $actor, $expectedRequest ), $event
            );
        } );
        $eventDispatcher->dispatch( InboxActivityEvent::NAME, new InboxActivityEvent(
            $follow,
            $actor,
            Request::create( 'https://example.com/actor/1/inbox' )
        ) );
        $this->assertTrue( $outboxDispatched );
    }

    public function testItChecksForFollowObject()
    {
        $eventDispatcher = new EventDispatcher();
        $contextProvider = new ContextProvider();
        $followHandler = new FollowHandler( true, $contextProvider );
        $eventDispatcher->addSubscriber( $followHandler );
        $outboxDispatched = false;
        $actor = TestActivityPubObject::fromArray( array(
            'id' => 'https://example.com/actor/1',
            'outbox' => 'https://example.com/actor/1/outbox',
        ) );
        $follow = array(
            'id' => 'https://elsewhere.com/activities/1',
            'type' => 'Follow',
            'object' => 'https://example.com/actor/2',
        );
        $eventDispatcher->addListener( OutboxActivityEvent::NAME, function ()
        use ( &$outboxDispatched ) {
            $outboxDispatched = true;
        } );
        $eventDispatcher->dispatch( InboxActivityEvent::NAME, new InboxActivityEvent(
            $follow,
            $actor,
            Request::create( 'https://example.com/actor/1/inbox' )
        ) );
        $this->assertFalse( $outboxDispatched );
    }
}

