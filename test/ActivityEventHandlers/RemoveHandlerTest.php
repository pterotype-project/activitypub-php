<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\ActivityEventHandlers\RemoveHandler;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RemoveHandlerTest extends APTestCase
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

    public function testHandleRemove()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/removes/1',
                        'type' => 'Remove',
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
                'expectedRemovedItemId' => 'https://elsewhere.com/notes/1',
            ),
            array(
                'id' => 'outboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/removes/1',
                        'type' => 'Remove',
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
                'expectedRemovedItemId' => 'https://example.com/notes/1',
            ),
            array(
                'id' => 'notAuthorizedTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/removes/1',
                        'type' => 'Remove',
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
                ->setMethods( array( 'removeItem' ) )
                ->getMock();
            if ( array_key_exists( 'expectedRemovedItemId', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'removeItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedRemovedItemId'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'removeItem' );
            }
            $removeHandler = new RemoveHandler( $objectsService, $collectionsService );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $removeHandler );
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->setExpectedException( $testCase['expectedException'] );
            }
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }
}