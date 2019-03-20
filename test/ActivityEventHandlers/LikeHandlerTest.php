<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\LikeHandler;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LikeHandlerTest extends APTestCase
{
    private static function getObjects()
    {
        return array(
            'https://example.com/notes/haslikes' => array(
                'id' => 'https://example.com/notes/haslikes',
                'type' => 'Note',
                'likes' => array(
                    'type' => 'Collection',
                    'items' => array(),
                ),
            ),
            'https://example.com/notes/nolikes' => array(
                'id' => 'https://example.com/notes/nolikes',
                'type' => 'Note',
            ),
        );
    }

    public function testHandleInbox()
    {
        $testCases = array(
            array(
                'id' => 'basicInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/likes/1',
                        'type' => 'Like',
                        'object' => 'https://example.com/notes/haslikes'
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1',
                            ) )
                        )
                    )
                ),
                'expectedNewLikes' => array(
                    'id' => 'https://elsewhere.com/likes/1',
                    'type' => 'Like',
                    'object' => 'https://example.com/notes/haslikes'
                ),
            ),
            array(
                'id' => 'dereferencedObjectInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/likes/1',
                        'type' => 'Like',
                        'object' => array(
                            'id' => 'https://example.com/notes/haslikes',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1',
                            ) )
                        )
                    )
                ),
                'expectedNewLikes' => array(
                    'id' => 'https://elsewhere.com/likes/1',
                    'type' => 'Like',
                    'object' => array(
                        'id' => 'https://example.com/notes/haslikes',
                    ),
                ),
            ),
            array(
                'id' => 'itCreatesLikesInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/likes/1',
                        'type' => 'Like',
                        'object' => array(
                            'id' => 'https://example.com/notes/nolikes',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1',
                            ) )
                        )
                    )
                ),
                'expectedNewLikes' => array(
                    'id' => 'https://elsewhere.com/likes/1',
                    'type' => 'Like',
                    'object' => array(
                        'id' => 'https://example.com/notes/nolikes',
                    ),
                ),
            ),
        );
        foreach( $testCases as $testCase ) {
            $objectsService = $this->getMock( ObjectsService::class );
            $objectsService->method( 'dereference')->willReturnCallback( function( $id ) {
                $objects = self::getObjects();
                if ( array_key_exists( $id, $objects ) ) {
                    return TestActivityPubObject::fromArray( $objects[$id] );
                } else {
                    return null;
                }
            });
            $objectsService->method( 'update')->willReturnCallback( function( $id, $arr ) {
                return TestActivityPubObject::fromArray( $arr );
            });
            $collectionsService = $this->getMockBuilder( CollectionsService::class )
                ->disableOriginalConstructor()
                ->setMethods( array( 'addItem' ) )
                ->getMock();
            if ( array_key_exists( 'expectedNewLikes', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewLikes'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $contextProvider = new ContextProvider();
            $likeHandler = new LikeHandler( $objectsService, $collectionsService, $contextProvider );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $likeHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }

    public function testHandleOutbox()
    {
        $testCases = array(
            array(
                'id' => 'basicOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/likes/1',
                        'type' => 'Like',
                        'object' => array(
                            'id' => 'https://elsewhere.com/notes/1'
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'liked' => array(
                            'type' => 'Collection',
                            'items' => array(),
                        )
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/outbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1',
                            ) ),
                        )
                    )
                ),
                'expectedNewLiked' => array(
                    'id' => 'https://elsewhere.com/notes/1',
                )
            ),
            array(
                'id' => 'createsLikedCollectionOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/likes/1',
                        'type' => 'Like',
                        'object' => array(
                            'id' => 'https://elsewhere.com/notes/1'
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/outbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1',
                            ) ),
                        )
                    )
                ),
                'expectedNewLiked' => array(
                    'id' => 'https://elsewhere.com/notes/1',
                )
            ),
        );
        foreach( $testCases as $testCase ) {
            $objectsService = $this->getMock( ObjectsService::class );
            $objectsService->method( 'dereference')->willReturnCallback( function( $id ) {
                $objects = self::getObjects();
                if ( array_key_exists( $id, $objects ) ) {
                    return TestActivityPubObject::fromArray( $objects[$id] );
                } else {
                    return null;
                }
            });
            $objectsService->method( 'update')->willReturnCallback( function( $id, $arr ) {
                return TestActivityPubObject::fromArray( $arr );
            });
            $collectionsService = $this->getMockBuilder( CollectionsService::class )
                ->disableOriginalConstructor()
                ->setMethods( array( 'addItem' ) )
                ->getMock();
            if ( array_key_exists( 'expectedNewLiked', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewLiked'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $contextProvider = new ContextProvider();
            $likeHandler = new LikeHandler( $objectsService, $collectionsService, $contextProvider );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $likeHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }
}