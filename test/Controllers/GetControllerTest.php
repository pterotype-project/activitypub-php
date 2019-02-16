<?php

namespace ActivityPub\Test\Controllers;

use ActivityPub\Auth\AuthService;
use ActivityPub\Controllers\GetController;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class GetControllerTest extends APTestCase
{
    /**
     * @var GetController
     */
    private $getController;
    /**
     * @var array
     */
    private $objects;

    public function setUp()
    {
        $this->objects = self::getObjects();
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will(
            $this->returnCallback( function ( $uri ) {
                if ( array_key_exists( $uri, $this->objects ) ) {
                    return TestActivityPubObject::fromArray( $this->objects[$uri] );
                }
                return null;
            } )
        );
        $authService = new AuthService();
        $contextProvider = new ContextProvider();
        $httpClient = $this->getMock( Client::class );
        $collectionsService = new CollectionsService(
            4,
            $authService,
            $contextProvider,
            $httpClient,
            new SimpleDateTimeProvider(),
            $this->getMock( EntityManager::class ),
            $objectsService
        );
        $this->getController = new GetController(
            $objectsService, $collectionsService, $authService
        );
    }

    private static function getObjects()
    {
        return array(
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
            'https://example.com/objects/4' => array(
                'id' => 'https://example.com/objects/4',
                'type' => 'Tombstone',
            ),
        );
    }

    public function testItRendersPersistedObject()
    {
        $request = Request::create( 'https://example.com/objects/1' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/1'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItThrowsNotFound()
    {
        $request = Request::create( 'https://example.com/objects/notreal' );
        $this->setExpectedException( NotFoundHttpException::class );
        $this->getController->handle( $request );
    }

    public function testItDeniesAccess()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $this->setExpectedException( UnauthorizedHttpException::class );
        $this->getController->handle( $request );
    }

    public function testItAllowsAccessToAuthedActor()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $request->attributes->set( 'actor', 'https://example.com/actor/1' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/2'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItAllowsAccessToAttributedActor()
    {
        $request = Request::create( 'https://example.com/objects/2' );
        $request->attributes->set( 'actor', 'https://example.com/actor/2' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/2'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItAllowsAccessToNoAudienceObject()
    {
        $request = Request::create( 'https://example.com/objects/3' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/3'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItDisregardsQueryParams()
    {
        $request = Request::create( 'https://example.com/objects/1?foo=bar&baz=qux' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/1'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItReturns410ForTombstones()
    {
        $request = Request::create( 'https://example.com/objects/4' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->objects['https://example.com/objects/4'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
        $this->assertEquals( 410, $response->getStatusCode() );
    }
}

