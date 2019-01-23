<?php
namespace ActivityPub\Test\Controllers;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Controllers\PostController;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class PostControllerTest extends TestCase
{
    const OBJECTS = array(
        'https://example.com/actor/1/inbox' => array(
            'id' => 'https://example.com/actor/1/inbox',
        ),
    );

    public function testPostController()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'query' )->will(
            $this->returnCallback( function( $query ) {
                if ( array_key_exists( 'id', $query ) &&
                     array_key_exists( $query['id'], self::OBJECTS ) ) {
                    return array( TestActivityPubObject::fromArray(
                        self::OBJECTS[$query['id']]
                    ) );
                } else {
                    return array();
                }
            } )
        );
        $testCases = array(
            array(
                'id' => 'basicInboxTest',
                'request' => Request::create(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    array(), array(), array(), array(),
                    '{"type": "Create"}'
                ),
                'requestAttributes' => array(
                    'signed' => true,
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'inbox' => array(
                            'id' => 'https://example.com/actor/1/inbox',
                        )
                    ) ),
                ),
                'expectedEventName' => InboxActivityEvent::NAME,
                'expectedEvent' => new InboxActivityEvent(
                    array( 'type' => 'Create' ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'inbox' => array(
                            'id' => 'https://example.com/actor/1/inbox',
                        )
                    ) ),
                    Request::create(
                        'https://example.com/actor/1/inbox',
                        Request::METHOD_POST,
                        array(), array(), array(), array(),
                        '{"type": "Create"}'
                    )
                ),
            ),
        );
        foreach ( $testCases as $testCase ) {
            $eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
                             ->setMethods( array( 'dispatch' ) )
                             ->getMock();
            if ( array_key_exists( 'expectedEvent', $testCase ) ) {
                $eventDispatcher->expects( $this->once() )
                    ->method( 'dispatch' )
                    ->with(
                        $this->equalTo($testCase['expectedEventName']),
                        $this->equalTo($testCase['expectedEvent'])
                    );
            }
            $postController = new PostController( $eventDispatcher, $objectsService );
            $request = $testCase['request'];
            if ( array_key_exists( 'requestAttributes', $testCase ) ) {
                $request->attributes->add( $testCase['requestAttributes'] );
            }
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->expectException( $testCase['expectedException'] );
            }
            $postController->handle( $request );
        }
    }
}
?>
