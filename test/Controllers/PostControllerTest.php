<?php

namespace ActivityPub\Test\Controllers;

use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Controllers\PostController;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PostControllerTest extends APTestCase
{
    /**
     * @var array
     */
    private $objects;
    /**
     * @var array
     */
    private $refs;

    public function provideTestPostController()
    {
        $this->objects = self::getObjects();
        $this->refs = self::getRefs();
        return array(
            array( array(
                'id' => 'basicInboxTest',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedEventName' => InboxActivityEvent::NAME,
                'expectedEvent' => new InboxActivityEvent(
                    array(
                        'type' => 'Create',
                        'actor' => 'https://elsewhere.com/actor/1'
                    ),
                    TestActivityPubObject::fromArray(
                        $this->objects['https://example.com/actor/1']
                    ),
                    $this->makeRequest(
                        'https://example.com/actor/1/inbox',
                        Request::METHOD_POST,
                        '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                        array(
                            'signed' => true,
                            'actor' => TestActivityPubObject::fromArray(
                                $this->objects['https://elsewhere.com/actor/1']
                            ),
                        )
                    )
                ),
            ) ),
            array( array(
                'id' => 'basicOutboxTest',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/outbox',
                    Request::METHOD_POST,
                    '{"type": "Create"}',
                    array(
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://example.com/actor/1']
                        ),
                    )
                ),
                'expectedEventName' => OutboxActivityEvent::NAME,
                'expectedEvent' => new OutboxActivityEvent(
                    array( 'type' => 'Create' ),
                    TestActivityPubObject::fromArray(
                        $this->objects['https://example.com/actor/1']
                    ),
                    $this->makeRequest(
                        'https://example.com/actor/1/outbox',
                        Request::METHOD_POST,
                        '{"type": "Create"}',
                        array(
                            'actor' => TestActivityPubObject::fromArray(
                                $this->objects['https://example.com/actor/1']
                            ),
                        )
                    )
                ),
            ) ),
            array( array(
                'id' => 'inboxRequestMustBeSigned',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ) ),
            array( array(
                'id' => 'outboxRequestsMustBeAuthed',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array()
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ) ),
            array( array(
                'id' => '404sIfNotFound',
                'request' => $this->makeRequest(
                    'https://example.com/actor/notreal/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => NotFoundHttpException::class,
            ) ),
            array( array(
                'id' => 'BadRequestIfNoBody',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => BadRequestHttpException::class,
            ) ),
            array( array(
                'id' => 'BadRequestIfMalformedBody',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    'this is not JSON',
                    array(
                        'signed' => 'true',
                        'actor' => TestActivityPubObject::fromArray(
                            $this->objects['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => BadRequestHttpException::class,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestPostController
     */
    public function testPostController( $testCase )
    {
        $this->objects = self::getObjects();
        $this->refs = self::getRefs();
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'query' )->will(
            $this->returnCallback( function ( $query ) {
                if ( array_key_exists( 'id', $query ) &&
                    array_key_exists( $query['id'], $this->objects ) ) {
                    $object = TestActivityPubObject::fromArray(
                        $this->objects[$query['id']]
                    );
                    if ( array_key_exists( $query['id'], $this->refs ) ) {
                        $ref = $this->refs[$query['id']];
                        $referencingObject = TestActivityPubObject::fromArray(
                            $this->objects[$ref['referencingObject']]
                        );
                        $referencingField = $referencingObject->getField( $ref['field'] );
                        $object->addReferencingField( $referencingField );
                    }
                    return array( $object );
                } else {
                    return array();
                }
            } )
        );
        $objectsService->method( 'dereference' )->will(
            $this->returnCallback( function ( $id ) {
                if ( array_key_exists( $id, $this->objects ) ) {
                    return TestActivityPubObject::fromArray( $this->objects[$id] );
                } else {
                    return null;
                }
            } )
        );
        $eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
            ->setMethods( array( 'dispatch' ) )
            ->getMock();
        if ( array_key_exists( 'expectedEvent', $testCase ) ) {
            $eventDispatcher->expects( $this->once() )
                ->method( 'dispatch' )
                ->with(
                    $this->equalTo( $testCase['expectedEventName'] ),
                    $this->equalTo( $testCase['expectedEvent'] )
                );
        }
        $postController = new PostController( $eventDispatcher, $objectsService );
        $request = $testCase['request'];
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $postController->handle( $request );
    }

    private static function getObjects()
    {
        return array(
            'https://example.com/actor/1/inbox' => array(
                'id' => 'https://example.com/actor/1/inbox',
            ),
            'https://example.com/actor/1/outbox' => array(
                'id' => 'https://example.com/actor/1/outbox',
            ),
            'https://example.com/actor/1' => array(
                'id' => 'https://example.com/actor/1',
                'inbox' => array(
                    'id' => 'https://example.com/actor/1/inbox',
                ),
                'outbox' => array(
                    'id' => 'https://example.com/actor/1/outbox',
                ),
            ),
            'https://elsewhere.com/actor/1' => array(
                'id' => 'https://elsewhere.com/actor/1',
            ),
        );
    }

    private static function getRefs()
    {
        return array(
            'https://example.com/actor/1/inbox' => array(
                'field' => 'inbox',
                'referencingObject' => 'https://example.com/actor/1',
            ),
            'https://example.com/actor/1/outbox' => array(
                'field' => 'outbox',
                'referencingObject' => 'https://example.com/actor/1',
            ),
        );
    }

    private function makeRequest( $uri, $method, $body, $attributes )
    {
        $request = Request::create(
            $uri, $method, array(), array(), array(), array(), $body
        );
        $request->attributes->add( $attributes );
        // This populates the pathInfo, requestUri, and baseUrl fields on the request:
        $request->getUri();
        return $request;
    }
}

