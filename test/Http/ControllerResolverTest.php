<?php
namespace ActivityPub\Test\Http;

use ActivityPub\Controllers\Inbox\DefaultInboxController;
use ActivityPub\Controllers\Outbox\DefaultOutboxController;
use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Controllers\InboxController;
use ActivityPub\Controllers\OutboxController;
use ActivityPub\Http\ControllerResolver;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestUtils\TestUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ControllerResolverTest extends TestCase
{
    const INBOX_URI = 'https://example.com/inbox';
    const OUTBOX_URI = 'https://example.com/outbox';

    private $controllerResolver;
    private $getObjectController;
    private $inboxController;
    private $outboxController;
    
    public function setUp()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'query' )->will(
            $this->returnCallback( function ( $query ) {
                if ( array_key_exists( 'inbox', $query ) &&
                     $query['inbox'] == self::INBOX_URI ) {
                    return array( TestUtils::objectFromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'inbox' => array(
                            'id' => 'https://example.com/actor/1/inbox',
                        ),
                    ) ) );
                }
                if ( array_key_exists( 'outbox', $query ) &&
                     $query['outbox'] == self::OUTBOX_URI ) {
                    return array( TestUtils::objectFromArray( array(
                        'id' => 'https://example.com/actor/1',
                        'outbox' => array(
                            'id' => 'https://example.com/actor/1/outbox',
                        ),
                    ) ) );
                }
                return array();
            })
        );
        $this->getObjectController = $this->createMock( GetObjectController::class );
        $this->inboxController = $this->createMock( InboxController::class );
        $this->outboxController = $this->createMock( OutboxController::class );
        $this->controllerResolver = new ControllerResolver(
            $objectsService,
            $this->getObjectController,
            $this->inboxController,
            $this->outboxController
        );
    }

    private function createRequestWithBody( $uri, $method, $body )
    {
        $json = json_encode( $body );
        return Request::create($uri, $method, array(), array(), array(), array(), $json);
    }

    public function testItReturnsGetObjectController()
    {
        $request = Request::create( 'https://example.com/object', Request::METHOD_GET );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertIsCallable( $controller );
        $this->assertEquals( array( $this->getObjectController, 'handle' ), $controller );
    }

    public function testItChecksForType()
    {
        $request = Request::create( 'https://example.com/inbox', Request::METHOD_POST );
        $this->expectException( BadRequestHttpException::class );
        $controller = $this->controllerResolver->getController( $request );
    }

    public function testItReturnsInboxController()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/inbox', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertTrue( $request->attributes->has( 'activity' ) );
        $this->assertEquals( array( 'type' => 'Foo' ), $request->attributes->get( 'activity' ) );
        $this->assertTrue( $request->attributes->has( 'inbox' ) );
        $this->assertEquals(
            array(
                'id' => 'https://example.com/actor/1/inbox',
            ),
            $request->attributes->get( 'inbox' )->asArray()
        );
        $this->assertEquals( array( $this->inboxController, 'handle' ), $controller );
    }

    public function testItReturnsOutboxController()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/outbox', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertTrue( $request->attributes->has( 'activity' ) );
        $this->assertEquals( array( 'type' => 'Foo' ), $request->attributes->get( 'activity' ) );
        $this->assertTrue( $request->attributes->has( 'outbox' ) );
        $this->assertEquals(
            array(
                'id' => 'https://example.com/actor/1/outbox',
            ),
            $request->attributes->get( 'outbox' )->asArray()
        );
        $this->assertEquals( array( $this->outboxController, 'handle' ), $controller );
    }

    public function testItDisallowsPostToInvalidUrl()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/object', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $this->expectException( NotFoundHttpException::class );
        $this->controllerResolver->getController( $request );
    }

    public function testItDisallowsNonGetPostMethods()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/inbox', Request::METHOD_PUT, array( 'type' => 'Foo' )
        );
        $this->expectException( MethodNotAllowedHttpException::class );
        $this->controllerResolver->getController( $request );
    }
}
?>
