<?php
namespace ActivityPub\Test\Activities;

use ActivityPub\Activities\FollowHandler;
use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class FollowHandlerTest extends TestCase
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
        $eventDispatcher->addListener( OutboxActivityEvent::NAME, function( $event, $name )
            use ( &$outboxDispatched, $actor )
        {
            $this->assertEquals( OutboxActivityEvent::NAME, $name );
            $outboxDispatched = true;
            $accept = array(
                '@context' => ContextProvider::DEFAULT_CONTEXT,
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
            $expectedRequest->attributes->set( 'actor', $actor );
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
        $eventDispatcher->addListener( OutboxActivityEvent::NAME, function( $event )
            use ( &$outboxDispatched )
            {
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
?>
