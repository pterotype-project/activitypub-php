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
                'activity' => array(),
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
                'activity' => array(),
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
                'activity' => array(
                    'id' => 'https://notexample.com/activity/1',
                    'type' => 'Create',
                    'actor' => 'https://notexample.com/actor/1',
                ),
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://notexample.com/activity/1',
                        'type' => 'Create',
                        'actor' => 'https://notexample.com/actor/1',
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
                'activity' => array(
                    'type' => 'Create',
                ),
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
            ),
        );
        foreach ( $testCases as $testCase ) {
            $activity = $testCase['activity'];
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
