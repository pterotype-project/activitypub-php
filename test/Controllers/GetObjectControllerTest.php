<?php
namespace ActivityPub\Test\Controllers;

use ActivityPub\Auth\AuthService;
use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestUtils\TestUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPUnit\Framework\TestCase;

class GetObjectControllerTest extends TestCase
{
    const OBJECTS = array(
        'https://example.com/objects/1' => array(
            'id' => 'https://example.com/objects/1',
            'object' => array(
                'id' => 'https://example.com/objects/2',
                'type' => 'Note',
            ),
            'audience' => array( 'https://www.w3.org/ns/activitystreams#Public' ),
            'type' => 'Create',
        ),
        'https://example.com/objects/2' => array(
            'id' => 'https://example.com/objects/2',
            'object' => array(
                'id' => 'https://example.com/objects/3',
                'type' => 'Note',
            ),
            'to' => array( 'https://example.com/actor/1' ),
            'type' => 'Create',
            'actor' => array(
                'id' => 'https://example.com/actor/2',
            ),
        ),
        'https://example.com/objects/3' => array(
            'id' => 'https://example.com/objects/3',
            'object' => array(
                'id' => 'https://example.com/objects/2',
                'type' => 'Note',
            ),
            'type' => 'Like',
            'actor' => array(
                'id' => 'https://example.com/actor/2',
            ),
        ),
    );

    private $getObjectController;

    public function setUp()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will(
            $this->returnCallback( function( $uri ) {
                if ( array_key_exists( $uri, self::OBJECTS ) ) {
                    return TestUtils::objectFromArray( self::OBJECTS[$uri] );
                }
            })
        );
        $authService = new AuthService();
        $contextProvider = new ContextProvider();
        $collectionsService = new CollectionsService( 4, $authService, $contextProvider );
        $this->getObjectController = new GetObjectController(
            $objectsService, $collectionsService, $authService
        );
    }

    public function testItRendersPersistedObject()
    {
        $request = Request::create( 'https://example.com/objects/1' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/1'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItThrowsNotFound()
    {
        $request = Request::create( 'https://example.com/objects/notreal' );
        $this->expectException( NotFoundHttpException::class );
        $this->getObjectController->handle( $request );
    }

    public function testItDeniesAccess()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $this->expectException( UnauthorizedHttpException::class );
        $this->getObjectController->handle( $request );
    }

    public function testItAllowsAccessToAuthedActor()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $request->attributes->set( 'actor', 'https://example.com/actor/1' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/2'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItAllowsAccessToAttributedActor()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $request->attributes->set( 'actor', 'https://example.com/actor/2' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/2'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItAllowsAccessToNoAudienceObject()
    {
        $request = Request::create( 'https://example.com/objects/3' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/3'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItDisregardsQueryParams()
    {
        $request = Request::create( 'https://example.com/objects/1?foo=bar&baz=qux' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/1'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }
}
?>
