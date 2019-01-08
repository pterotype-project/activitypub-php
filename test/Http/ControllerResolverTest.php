<?php
namespace ActivityPub\Test\Http;

use ActivityPub\Controllers\Inbox\DefaultInboxController;
use ActivityPub\Controllers\Outbox\DefaultOutboxController;
use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Http\ControllerResolver;
use ActivityPub\Objects\ObjectsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class ControllerResolverTest extends TestCase
{
    const INBOX_URI = 'https://example.com/inbox';
    const OUTBOX_URI = 'https://example.com/outbox';

    private $controllerResolver;
    
    public function setUp()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'query' )->will(
            $this->returnCallback( function ( $query ) {
                if ( array_key_exists( 'inbox', $query ) &&
                     $query['inbox'] == self::INBOX_URI ) {
                    return array( 'objectWithInbox' );
                }
                if ( array_key_exists( 'outbox', $query ) &&
                     $query['outbox'] == self::OUTBOX_URI ) {
                    return array( 'objectWithOutbox' );
                }
                return array();
            })
        );
        $this->controllerResolver = new ControllerResolver( $objectsService );
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
        $this->assertInstanceOf( GetObjectController::class, $controller[0] );
        $this->assertEquals( 'handle', $controller[1] );
    }

    public function testItChecksForType()
    {
        $request = Request::create( 'https://example.com/inbox', Request::METHOD_POST );
        $this->expectException( BadRequestHttpException::class );
        $controller = $this->controllerResolver->getController( $request );
    }

    public function testItReturnsDefaultInboxController()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/inbox', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertIsCallable( $controller );
        $this->assertInstanceOf( DefaultInboxController::class, $controller[0] );
        $this->assertEquals( 'handle', $controller[1] );
    }

    public function testItReturnsDefaultOutboxController()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/outbox', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertIsCallable( $controller );
        $this->assertInstanceOf( DefaultOutboxController::class, $controller[0] );
        $this->assertEquals( 'handle', $controller[1] );
    }

    public function testItRegistersNewInboxController()
    {
        $this->controllerResolver->registerInboxController( function() {
            return 'barCallable';
        }, 'Bar' );
        $request = $this->createRequestWithBody(
            'https://example.com/inbox', Request::METHOD_POST, array( 'type' => 'Bar' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertIsCallable( $controller );
        $this->assertEquals( 'barCallable', call_user_func( $controller ) );
    }

    public function testItRegistersNewOutboxController()
    {
        $this->controllerResolver->registerOutboxController( function() {
            return 'barCallable';
        }, 'Bar' );
        $request = $this->createRequestWithBody(
            'https://example.com/outbox', Request::METHOD_POST, array( 'type' => 'Bar' )
        );
        $controller = $this->controllerResolver->getController( $request );
        $this->assertIsCallable( $controller );
        $this->assertEquals( 'barCallable', call_user_func( $controller ) );
    }

    public function testItDisallowsPostToInvalidUrl()
    {
        $request = $this->createRequestWithBody(
            'https://example.com/object', Request::METHOD_POST, array( 'type' => 'Foo' )
        );
        $this->expectException( MethodNotAllowedHttpException::class );
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
