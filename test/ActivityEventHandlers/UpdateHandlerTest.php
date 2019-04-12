<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\ActivityEventHandlers\UpdateHandler;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UpdateHandlerTest extends APTestCase
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var array
     */
    private $objects;

    public function setUp()
    {
        $this->objects = self::getObjects();
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will( $this->returnCallback(
            function ( $id ) {
                if ( array_key_exists( $id, $this->objects ) ) {
                    return TestActivityPubObject::fromArray( $this->objects[$id] );
                }
                return null;
            }
        ) );
        $objectsService->method( 'update' )->will( $this->returnCallback(
            function ( $id, $updateFields ) {
                if ( array_key_exists( $id, $this->objects ) ) {
                    $existing = $this->objects[$id];
                    foreach ( $updateFields as $field => $newValue ) {
                        if ( $newValue === null && array_key_exists( $field, $existing ) ) {
                            unset( $existing[$field] );
                        } else {
                            $existing[$field] = $newValue;
                        }
                    }
                    return TestActivityPubObject::fromArray( $existing );
                }
                return null;
            }
        ) );
        $updateHandler = new UpdateHandler( $objectsService );
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber( $updateHandler );
    }

    private static function getObjects()
    {
        return array(
            'https://elsewhere.com/objects/1' => array(
                'id' => 'https://elsewhere.com/objects/1',
                'attributedTo' => 'https://elsewhere.com/actors/1',
            ),
            'https://example.com/objects/1' => array(
                'id' => 'https://example.com/objects/1',
                'attributedTo' => 'https://example.com/actors/1',
                'type' => 'Note',
                'content' => 'This is a note',
            ),
        );
    }

    public function provideTestUpdateHandler()
    {
        return array(
            array( array(
                'id' => 'basicInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://elsewhere.com/objects/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ) ) )
                    )
                ),
                'expectedEvent' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://elsewhere.com/objects/1',
                        ),

                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ) ) )
                    )
                ),
            ) ),
            array( array(
                'id' => 'basicOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'content' => 'This is an updated note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/outbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://example.com/actors/1',
                        ) ) )
                    )
                ),
                'expectedEvent' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'attributedTo' => 'https://example.com/actors/1',
                            'content' => 'This is an updated note',
                        ),

                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/outbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://example.com/actors/1',
                        ) ) )
                    )
                ),
            ) ),
            array( array(
                'id' => 'checksInboxAuth',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://elsewhere.com/objects/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/2',
                            ) )
                        )
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ) ),
            array( array(
                'id' => 'checksOutboxAuth',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Update',
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/outbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/2',
                            ) )
                        )
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestUpdateHandler
     */
    public function testUpdateHandler( $testCase )
    {
        $event = $testCase['event'];
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $this->eventDispatcher->dispatch( $testCase['eventName'], $event );
        if ( array_key_exists( 'expectedEvent', $testCase ) ) {
            $this->assertEquals(
                $testCase['expectedEvent'], $event, "Error on test $testCase[id]"
            );
        }
    }
}

