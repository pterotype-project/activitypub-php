<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\ActivityEventHandlers\UndoHandler;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UndoHandlerTest extends APTestCase
{
    public function provideTestUndoHandler()
    {
        $followForUndoFollowInbox = TestActivityPubObject::fromArray( array(
            'id' => 'https://elsewhere.com/follows/1',
            'type' => 'Follow',
            'actor' => array(
                'id' => 'https://elsewhere.com/actors/1',
            ),
            'object' => array(
                'id' => 'https://example.com/actors/1',
                'followers' => array(
                    'id' => 'https://example.com/actors/1/followers',
                )
            ),
        ) );
        $likeForUndoLikeInbox = TestActivityPubObject::fromArray( array(
            'id' => 'https://elsewhere.com/likes/1',
            'type' => 'Like',
            'actor' => array(
                'id' => 'https://elsewhere.com/actors/1',
            ),
            'object' => array(
                'id' => 'https://example.com/notes/1',
                'likes' => array(
                    'id' => 'https://example.com/notes/1/likes',
                ),
            ),
        ) );
        $followForUndoFollowOutbox = TestActivityPubObject::fromArray( array(
            'id' => 'https://example.com/follows/1',
            'type' => 'Follow',
            'actor' => array(
                'id' => 'https://example.com/actors/1',
                'following' => array(
                    'id' => 'https://example.com/actors/1/following',
                ),
            ),
            'object' => 'https://elsewhere.com/actors/1',
        ) );
        $likeForUndoLikeOutbox = TestActivityPubObject::fromArray( array(
            'id' => 'https://example.com/likes/1',
            'type' => 'Like',
            'actor' => array(
                'id' => 'https://example.com/actors/1',
                'liked' => array(
                    'id' => 'https://example.com/actors/1/liked',
                ),
            ),
            'object' => array(
                'id' => 'https://elsewhere.com/notes/1',
            ),
        ) );
        return array(
            array( array(
                'id' => 'undoFollowInbox',
                'objects' => array(
                    'https://elsewhere.com/follows/1' => $followForUndoFollowInbox,
                ),
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/undos/1',
                        'type' => 'Undo',
                        'actor' => array(
                            'id' => 'https://elsewhere.com/actors/1'
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/follows/1',
                            'type' => 'Follow',
                            'actor' => 'https://elsewhere.com/actors/1',
                            'object' => 'https://example.com/actors/1',
                        )
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    Request::create( 'https://example.com/actors/1/inbox' )
                ),
                'collectionToRemoveFrom' => $followForUndoFollowInbox['object']['followers'],
                'itemToRemove' => 'https://elsewhere.com/actors/1',
            ) ),
            array( array(
                'id' => 'undoLikeInbox',
                'objects' => array(
                    'https://elsewhere.com/likes/1' => $likeForUndoLikeInbox,
                ),
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/undos/1',
                        'type' => 'Undo',
                        'actor' => 'https://elsewhere.com/actors/1',
                        'object' => 'https://elsewhere.com/likes/1'
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    Request::create( 'https://example.com/actors/1/inbox' )
                ),
                'collectionToRemoveFrom' => $likeForUndoLikeInbox['object']['likes'],
                'itemToRemove' => 'https://elsewhere.com/likes/1',
            ) ),
            array( array(
                'id' => 'undoFollowOutbox',
                'objects' => array(
                    'https://example.com/follows/1' => $followForUndoFollowOutbox,
                ),
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/undos/1',
                        'type' => 'Undo',
                        'actor' => 'https://example.com/actors/1',
                        'object' => 'https://example.com/follows/1',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    Request::create( 'https://example.com/actors/1/outbox' )
                ),
                'collectionToRemoveFrom' => $followForUndoFollowOutbox['actor']['following'],
                'itemToRemove' => 'https://elsewhere.com/actors/1',
            ) ),
            array( array(
                'id' => 'undoLikeOutbox',
                'objects' => array(
                    'https://example.com/likes/1' => $likeForUndoLikeOutbox,
                ),
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/undos/1',
                        'type' => 'Undo',
                        'actor' => 'https://example.com/actors/1',
                        'object' => 'https://example.com/likes/1',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    Request::create( 'https://example.com/actors/1/outbox' )
                ),
                'collectionToRemoveFrom' => $likeForUndoLikeOutbox['actor']['liked'],
                'itemToRemove' => $likeForUndoLikeOutbox['object']['id']
            ) ),
            array( array(
                'id' => 'undoActorDoesNotMatchObjectActor',
                'objects' => array(
                    'https://elsewhere.com/follows/1' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://elsewhere.com/follows/1',
                        'type' => 'Follow',
                        'actor' => array(
                            'id' => 'https://somewhereelse.com/actors/1',
                        ),
                        'object' => 'https://example.com/actors/1',
                    ) )
                ),
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/undos/1',
                        'type' => 'Undo',
                        'actor' => 'https://elsewhere.com/actors/1',
                        'object' => 'https://elsewhere.com/follows/1',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    Request::create( 'https://example.com/actors/1/inbox' )
                ),
                'expectedException' => AccessDeniedHttpException::class,
            ) )
        );
    }

    /**
     * @dataProvider provideTestUndoHandler
     */
    public function testUndoHandler( $testCase )
    {
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will(
            $this->returnCallback(
                function( $id) use ( $testCase ) {
                    $objects = $testCase['objects'];
                    if ( array_key_exists( $id, $objects ) ) {
                        return $objects[$id];
                    } else {
                        return null;
                    }
                }
            )
        );
        $collectionsService = $this->getMockBuilder( CollectionsService::class )
            ->disableOriginalConstructor()
            ->setMethods( array( 'removeItem' ) )
            ->getMock();
        if ( array_key_exists( 'collectionToRemoveFrom', $testCase ) ) {
            $collectionsService->expects( $this->once() )
                ->method( 'removeItem' )
                ->with(
                    $testCase['collectionToRemoveFrom'],
                    $testCase['itemToRemove']
                );
        } else {
            $collectionsService->expects( $this->never() )->method( 'removeItem' );
        }
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $undoHandler = new UndoHandler( $objectsService, $collectionsService );
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber( $undoHandler );
        $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
    }
}