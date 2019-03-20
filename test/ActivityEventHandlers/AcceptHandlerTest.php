<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\AcceptHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AcceptHandlerTest extends APTestCase
{
    public static function getObjects()
    {
        return array(
            'https://example.com/follows/1' => array(
                'id' => 'https://example.com/follows/1',
                'type' => 'Follow',
                'actor' => array(
                    'id' => 'https://example.com/actors/1',
                    'following' => array(
                        'type' => 'OrderedCollection',
                    ),
                ),
                'object' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                )
            ),
            'https://example.com/actors/1' => array(
                'id' => 'https://example.com/actors/1',
                'following' => array(
                    'type' => 'OrderedCollection',
                )
            ),
            'https://example.com/follows/2' => array(
                'id' => 'https://example.com/follows/2',
                'type' => 'Follow',
                'actor' => array(
                    'id' => 'https://example.com/actors/2'
                ),
                'object' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                ),
            ),
            'https://elsewhere.com/follows/1' => array(
                'id' => 'https://elsewhere.com/follows/1',
                'type' => 'Follow',
                'actor' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                ),
                'object' => 'https://example.com/actors/1',
            ),
            'https://example.com/actors/3' => array(
                'id' => 'https://example.com/actors/3',
            ),
            'https://example.com/follows/3' => array(
                'id' => 'https://example.com/follows/3',
                'actor' => array(
                    'id' => 'https://example.com/actors/3',
                ),
                'object' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                )
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
                        'id' => 'https://elsewhere.com/accepts/1',
                        'type' => 'Accept',
                        'actor' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/follows/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'following' => array(
                            'type' => 'OrderedCollection',
                        ),
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array (
                            'id' => 'https://elsewhere.com/actors/1'
                        ) ) )
                    )
                ),
                'expectedNewFollowing' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                )
            ),
            array(
                'id' => 'acceptForSomeoneElsesFollow',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/accepts/1',
                        'type' => 'Accept',
                        'actor' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/follows/2',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'following' => array(
                            'type' => 'OrderedCollection',
                        ),
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array (
                            'id' => 'https://elsewhere.com/actors/1'
                        ) ) )
                    )
                ),
            ),
            array(
                'id' => 'noFollowingTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/accepts/2',
                        'type' => 'Accept',
                        'actor' => array(
                            'id' => 'https://elsewhere.com/actors/2',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/follows/3',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/3',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/3/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array (
                            'id' => 'https://elsewhere.com/actors/1'
                        ) ) )
                    )
                ),
                'expectedNewFollowing' => array(
                    'id' => 'https://elsewhere.com/actors/1',
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
            if ( array_key_exists( 'expectedNewFollowing', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewFollowing'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $contextProvider = new ContextProvider();
            $acceptHandler = new AcceptHandler( $objectsService, $collectionsService, $contextProvider );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $acceptHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }

    public function testHandleOutbox()
    {
        $testCases = array(
            array(
                'id' => 'notAutoAccepted',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/accepts/1',
                        'type' => 'Accept',
                        'object' => array(
                            'id' => 'https://elsewhere.com/follows/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'followers' => array(
                            'type' => 'OrderedCollection',
                        )
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/outbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1',
                                'followers' => array(
                                    'type' => 'OrderedCollection',
                                )
                            ) ),
                        )
                    )
                ),
                'expectedNewFollower' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                )
            ),
            array(
                'id' => 'autoAccepted',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/accepts/1',
                        'type' => 'Accept',
                        'actor' => array(
                            'id' => 'https://example.com/actor/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/follows/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'followers' => array(
                            'type' => 'OrderedCollection',
                        )
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/outbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1',
                                'followers' => array(
                                    'type' => 'OrderedCollection',
                                )
                            ) ),
                            'follow' => array(
                                'id' => 'https://elsewhere.com/follow/4',
                                'type' => 'Follow',
                                'actor' => array(
                                    'id' => 'https://elsewhere.com/actors/1',
                                ),
                                'object' => array(
                                    'id' => 'https://example.com/actors/1',
                                ),
                            ),
                        )
                    )
                ),
                'expectedNewFollower' => array(
                    'id' => 'https://elsewhere.com/actors/1',
                )
            ),
            array(
                'id' => 'noFollowersCollection',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/accepts/1',
                        'type' => 'Accept',
                        'object' => array(
                            'id' => 'https://elsewhere.com/follows/1',
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
                'expectedNewFollower' => array(
                    'id' => 'https://elsewhere.com/actors/1',
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
            if ( array_key_exists( 'expectedNewFollower', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewFollower'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $contextProvider = new ContextProvider();
            $acceptHandler = new AcceptHandler( $objectsService, $collectionsService, $contextProvider );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $acceptHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }
}

