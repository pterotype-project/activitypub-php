<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\AnnounceHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AnnounceHandlerTest extends APTestCase
{
    private static function getObjects()
    {
        return array(
            'https://example.com/notes/withshares' => array(
                'id' => 'https://example.com/notes/withshares',
                'shares' => array(
                    'type' => 'Collection',
                    'items' => array(),
                ),
            ),
            'https://example.com/notes/withoutshares' => array(
                'id' => 'https://example.com/notes/withoutshares',
            ),
        );
    }

    public function provideTestAnnounceHandler()
    {
        return array(
            array( array(
                'id' => 'basicInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/announces/1',
                        'type' => 'Announce',
                        'object' => 'https://example.com/notes/withshares',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1',
                            ) ),
                        )
                    )
                ),
                'expectedNewShares' => array(
                    'id' => 'https://elsewhere.com/announces/1',
                    'type' => 'Announce',
                    'object' => 'https://example.com/notes/withshares',
                )
            ) ),
            array( array(
                'id' => 'generatesSharesCollectionTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/announces/1',
                        'type' => 'Announce',
                        'object' => 'https://example.com/notes/withoutshares',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/actors/1/inbox',
                        array(
                            'actor' => TestActivityPubObject::fromArray( array(
                                'id' => 'https://elsewhere.com/actors/1',
                            ) ),
                        )
                    )
                ),
                'expectedNewShares' => array(
                    'id' => 'https://elsewhere.com/announces/1',
                    'type' => 'Announce',
                    'object' => 'https://example.com/notes/withoutshares',
                )
            ) ),
        );
    }

    /**
     * @dataProvider provideTestAnnounceHandler
     */
    public function testAnnounceHandler( $testCase )
    {
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
            if ( array_key_exists( 'expectedNewShares', $testCase ) ) {
                $collectionsService->expects( $this->once() )
                    ->method( 'addItem' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedNewShares'] )
                    );
            } else {
                $collectionsService->expects( $this->never() )
                    ->method( 'addItem' );
            }
            $contextProvider = new ContextProvider();
            $announceHandler = new AnnounceHandler( $objectsService, $collectionsService, $contextProvider );
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addSubscriber( $announceHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
    }
}