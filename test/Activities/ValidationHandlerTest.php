<?php
namespace ActivityPub\Test\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Activities\ValidationHandler;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ValidationHandlerTest extends TestCase
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    
    public function setUp()
    {
        $this->eventDispatcher = new EventDispatcher();
        $validationHandler = new ValidationHandler();
        $this->eventDispatcher->addSubscriber( $validationHandler );
    }
    public function testValidationHandler()
    {
        $testCases = array(
            array(
                'id' => 'inboxRequiredFields',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://notexample.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: type,id,actor',
            ),
            array(
                'id' => 'outboxRequiredFields',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: type',
            ),
            array(
                'id' => 'inboxPassesValidActivity',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://notexample.com/activity/1',
                        'type' => 'Create',
                        'actor' => 'https://notexample.com/actor/1',
                        'object' => array(
                            'type' => 'Note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://notexample.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
            ),
            array(
                'id' => 'outboxPassesValidActivity',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'type' => 'Create',
                        'object' => array(
                            'type' => 'Note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
            ),
            array(
                'id' => 'outboxRequiresObjectFields',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'type' => 'Create',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: object',
            ),
            array(
                'id' => 'inboxRequiresObjectFields',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'type' => 'Create',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://notexample.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: object',
            ),
            array(
                'id' => 'inboxRequiresTargetFields',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'type' => 'Add',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://notexample.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: target',
            ),
            array(
                'id' => 'outboxRequiresTargetFields',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'type' => 'Remove',
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://notexample.com/actor/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com' )
                ),
                'expectedException' => BadRequestHttpException::class,
                'expectedExceptionMessage' => 'Missing activity fields: target',
            ),
        );
        foreach ( $testCases as $testCase ) {
            $event = $testCase['event'];
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->expectException(
                    $testCase['expectedException'],
                    "Error on test $testCase[id]"
                );
            }
            if ( array_key_exists( 'expectedExceptionMessage', $testCase ) ) {
                $this->expectExceptionMessage(
                    $testCase['expectedExceptionMessage'],
                    "Error on test $testCase[id]"
                );
            }
            $this->eventDispatcher->dispatch( $testCase['eventName'], $event );
        }
    }
}
?>
