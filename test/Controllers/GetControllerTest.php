<?php

namespace ActivityPub\Test\Controllers;

use ActivityPub\Auth\AuthService;
use ActivityPub\Controllers\GetController;
use ActivityPub\Objects\BlockService;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use Exception;
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
                    return $this->objects[$uri];
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
        $blockService = $this->getMock( BlockService::class );
        $blockService->method( 'getBlockedActorIds' )->will(
            $this->returnValue( array( 'https://elsewhere.com/actors/blocked' ) )
        );
        $this->getController = new GetController(
            $objectsService, $collectionsService, $authService, $blockService
        );
    }

    private static function getObjects()
    {
        $actorObject = TestActivityPubObject::fromArray( array(
            'id' => 'https://example.com/actors/1',
            'inbox' => array(
                'id' => 'https://example.com/actors/1/inbox',
                'type' => 'OrderedCollection',
                'orderedItems' => array(
                    array(
                        'id' => 'https://elsewhere.com/objects/1',
                        'actor' => 'https://elsewhere.com/actors/blocked',
                    ),
                    array(
                        'id' => 'https://elsewhere.com/objects/2',
                        'actor' => 'https://elsewhere.com/actors/notblocked',
                    )
                ),
            ),
        ) );
        $inboxObject = $actorObject['inbox'];
        return array(
            'https://example.com/objects/1' => TestActivityPubObject::fromArray( array(
                'id' => 'https://example.com/objects/1',
                'object' => array(
                    'id' => 'https://example.com/objects/2',
                    'type' => 'Note',
                ),
                'audience' => array( 'https://www.w3.org/ns/activitystreams#Public' ),
                'type' => 'Create',
            ) ),
            'https://example.com/objects/2' => TestActivityPubObject::fromArray( array(
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
            ) ),
            'https://example.com/objects/3' => TestActivityPubObject::fromArray( array(
                'id' => 'https://example.com/objects/3',
                'object' => array(
                    'id' => 'https://example.com/objects/2',
                    'type' => 'Note',
                ),
                'type' => 'Like',
                'actor' => array(
                    'id' => 'https://example.com/actor/2',
                ),
            ) ),
            'https://example.com/objects/4' => TestActivityPubObject::fromArray( array(
                'id' => 'https://example.com/objects/4',
                'type' => 'Tombstone',
            ) ),
            'https://example.com/actors/1' => $actorObject,
            'https://example.com/actors/1/inbox' => $inboxObject,
        );
    }

    private function getObjectArray( $id )
    {
        $obj = $this->objects[$id];
        return $obj->asArray();
    }

    public function testItRendersPersistedObject()
    {
        $request = Request::create( 'https://example.com/objects/1' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( $this->getObjectArray('https://example.com/objects/1' ) ),
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
            json_encode( $this->getObjectArray( 'https://example.com/objects/2' ) ),
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
            json_encode( $this->getObjectArray( 'https://example.com/objects/2' ) ),
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
            json_encode( $this->getObjectArray( 'https://example.com/objects/3' ) ),
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
            json_encode( $this->getObjectArray( 'https://example.com/objects/1' ) ),
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
            json_encode( $this->getObjectArray( 'https://example.com/objects/4' ) ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
        $this->assertEquals( 410, $response->getStatusCode() );
    }

    public function testItFiltersInboxForBlockedActors()
    {
        $request = Request::create( 'https://example.com/actors/1/inbox' );
        $response = $this->getController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode(
                array(
                    'id' => 'https://example.com/actors/1/inbox',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/actors/1/inbox?offset=0',
                        'type' => 'OrderedCollectionPage',
                        'orderedItems' => array(
                            array(
                                'id' => 'https://elsewhere.com/objects/2',
                                'actor' => 'https://elsewhere.com/actors/notblocked',
                            ),
                        ),
                        'partOf' => 'https://example.com/actors/1/inbox',
                        'startIndex' => 0,
                    ),
                )
            ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }
}

