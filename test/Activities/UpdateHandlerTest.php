<?php
namespace ActivityPub\Test\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Activities\UpdateHandler;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class UpdateHandlerTest extends TestCase
{
    const OBJECTS = array(
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

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function setUp()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will( $this->returnCallback(
            function( $id ) {
                if ( array_key_exists( $id, self::OBJECTS ) ) {
                    return TestActivityPubObject::fromArray( self::OBJECTS[$id] );
                }
            }
        ) );
        $objectsService->method( 'update' )->will( $this->returnCallback(
            function( $id, $updateFields ) {
                if ( array_key_exists( $id, self::OBJECTS ) ) {
                    $existing = self::OBJECTS[$id];
                    foreach ( $updateFields as $field => $newValue ) {
                        if ( $newValue === null && array_key_exists( $field, $existing ) ) {
                            unset( $existing[$field] );
                        } else {
                            $existing[$field] = $newValue;
                        }
                    }
                    return TestActivityPubObject::fromArray( $existing );
                }
            }
        ) );
        $updateHandler = new UpdateHandler( $objectsService );
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber( $updateHandler );
    }

    public function testUpdateHandler()
    {
        $testCases = array(
            array(
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
            ),
            array(
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
            ),
            array(
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
            ),
            array(
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
            ),
        );
        foreach ( $testCases as $testCase ) {
            $event = $testCase['event'];
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->expectException( $testCase['expectedException'] );
            }
            $this->eventDispatcher->dispatch( $testCase['eventName'], $event );
            if ( array_key_exists( 'expectedEvent', $testCase ) ) {
                $this->assertEquals(
                    $testCase['expectedEvent'], $event, "Error on test $testCase[id]"
                );
            }
        }
    }

    public static function requestWithAttributes( $uri, $attributes )
    {
        $request = Request::create( $uri );
        $request->attributes->add( $attributes );
        return $request;
    }
}
?>
