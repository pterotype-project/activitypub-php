<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\AddHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddHandlerTest extends APTestCase
{
    private static function getObjects()
    {
        return array(
            'https://elsewhere.com/collections/1' => array(
                'id' => 'https://elsewhere.com/collections/1',
            ),
            'https://example.com/collections/1' => array(
                'id' => 'https://example.com/collections/1',
            ),
        );
    }

    public function testHandleAdd()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/adds/1',
                        'type' => 'Add',
                        'object' => array(
                            'id' => 'https://elsewhere.com/notes/1',
                        ),
                        'target' => array(
                            'id' => 'https://elsewhere.com/collections/1'
                        )
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1'
                            ) )
                        )
                    )
                ),
                'expectedNewItem' => array(
                    'id' => 'https://elsewhere.com/notes/1',
                )
            ),
            array(
                'id' => 'outboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/adds/1',
                        'type' => 'Add',
                        'object' => array(
                            'id' => 'https://example.com/notes/1',
                        ),
                        'target' => array(
                            'id' => 'https://example.com/collections/1'
                        )
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1'
                            ) )
                        )
                    )
                ),
                'expectedNewItem' => array(
                    'id' => 'https://example.com/notes/1',
                )
            ),
            array(
                'id' => 'notAuthorizedTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/adds/1',
                        'type' => 'Add',
                        'object' => array(
                            'id' => 'https://example.com/notes/1',
                        ),
                        'target' => array(
                            'id' => 'https://elsewhere.com/collections/1'
                        )
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://example.com/actors/1'
                            ) )
                        )
                    )
                ),
                'expectedException' => AccessDeniedHttpException::class,
            ),
        );
        foreach ( $testCases as $testCase ) {
            $objectsService = $this->getMock( ObjectsService::class );
            $objectsService->method( 'dereference')->willReturnCallback( function( $id ) {
                $objects = self::getObjects();
                if ( array_key_exists( $id, $objects ) ) {
                    return TestActivityPubObject::fromArray( $objects[$id] );
                } else {
                    return null;
                }
            });
            $collectionsService = $this->getMockBuilder( CollectionsService::class )
                ->disableOriginalConstructor()
                ->setMethods( array( 'addItem' ) )
                ->getMock();
            if ( array_key_exists( 'expectedNewItem', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewItem'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $addHandler = new AddHandler( $objectsService, $collectionsService );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $addHandler );
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->setExpectedException( $testCase['expectedException'] );
            }
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }
}