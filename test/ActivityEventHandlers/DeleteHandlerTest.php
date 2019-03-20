<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\DeleteHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use DateTime;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DeleteHandlerTest extends APTestCase
{
    public function testDeleteHandler()
    {
        $testCases = array(
            array(
                'id' => 'basicInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Delete',
                        'object' => 'https://elsewhere.com/objects/1'
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
                'expectedTombstone' => array(
                    '@context' => 'https://www.w3.org/ns/activitystreams',
                    'id' => 'https://elsewhere.com/objects/1',
                    'formerType' => 'Note',
                    'type' => 'Tombstone',
                    'deleted' => '2014-01-05T21:31:40+0000',
                ),
            ),
            array(
                'id' => 'basicOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Delete',
                        'object' => 'https://example.com/objects/1'
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
                'expectedTombstone' => array(
                    '@context' => 'https://www.w3.org/ns/activitystreams',
                    'id' => 'https://example.com/objects/1',
                    'formerType' => 'Note',
                    'type' => 'Tombstone',
                    'deleted' => '2014-01-05T21:31:40+0000',
                ),
            ),
            array(
                'id' => 'outboxAuthTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Delete',
                        'object' => 'https://example.com/objects/1'
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/outbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://example.com/actors/2',
                        ) ) )
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ),
            array(
                'id' => 'inboxAuthTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Delete',
                        'object' => 'https://elsewhere.com/objects/1'
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                    self::requestWithAttributes(
                        'https://example.com/inbox',
                        array( 'actor' => TestActivityPubObject::fromArray( array(
                            'id' => 'https://elsewhere.com/actors/2',
                        ) ) )
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ),
        );
        foreach ( $testCases as $testCase ) {
            $eventDispatcher = new EventDispatcher();
            $dateTimeProvider = new TestDateTimeProvider( array(
                'activities.delete' => DateTime::createFromFormat(
                    DateTime::RFC2822, 'Sun, 05 Jan 2014 21:31:40 GMT'
                ),
            ) );
            $objectsService = $this->getMockBuilder( ObjectsService::class )
                ->disableOriginalConstructor()
                ->setMethods( array( 'dereference', 'replace' ) )
                ->getMock();
            $objectsService->method( 'dereference' )->will( $this->returnCallback(
                function ( $id ) {
                    if ( array_key_exists( $id, self::getObjects() ) ) {
                        $objects = self::getObjects();
                        return TestActivityPubObject::fromArray( $objects[$id] );
                    }
                    return null;
                }
            ) );
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->setExpectedException( $testCase['expectedException'] );
            } else {
                $objectsService->expects( $this->once() )
                    ->method( 'replace' )
                    ->with(
                        $this->anything(),
                        $this->equalTo( $testCase['expectedTombstone'] )
                    );
            }
            $deleteHandler = new DeleteHandler( $dateTimeProvider, $objectsService );
            $eventDispatcher->addSubscriber( $deleteHandler );
            $eventDispatcher->dispatch( $testCase['eventName'], $testCase['event'] );
        }
    }

    private static function getObjects()
    {
        return array(
            'https://elsewhere.com/objects/1' => array(
                'id' => 'https://elsewhere.com/objects/1',
                'type' => 'Note',
                'attributedTo' => 'https://elsewhere.com/actors/1',
            ),
            'https://example.com/objects/1' => array(
                'id' => 'https://example.com/objects/1',
                'type' => 'Note',
                'attributedTo' => 'https://example.com/actors/1',
            )
        );
    }
}

