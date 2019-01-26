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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class PostControllerTest extends TestCase
{
    const OBJECTS = array(
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
    const REFS = array(
        'https://example.com/actor/1/inbox' => array(
            'field' => 'inbox',
            'referencingObject' => 'https://example.com/actor/1',
        ),
        'https://example.com/actor/1/outbox' => array(
            'field' => 'outbox',
            'referencingObject' => 'https://example.com/actor/1',
        ),
    );

    public function testPostController()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'query' )->will(
            $this->returnCallback( function( $query ) {
                if ( array_key_exists( 'id', $query ) &&
                     array_key_exists( $query['id'], self::OBJECTS ) ) {
                    $object = TestActivityPubObject::fromArray(
                        self::OBJECTS[$query['id']]
                    );
                    if ( array_key_exists( $query['id'], self::REFS ) ) {
                        $ref = self::REFS[$query['id']];
                        $referencingObject = TestActivityPubObject::fromArray(
                            self::OBJECTS[$ref['referencingObject']]
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
            $this->returnCallback( function( $id ) {
                if ( array_key_exists( $id, self::OBJECTS ) ) {
                    return TestActivityPubObject::fromArray( self::OBJECTS[$id] );
                } else {
                    return null;
                }
            } )
        );
        $testCases = array(
            array(
                'id' => 'basicInboxTest',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://elsewhere.com/actor/1']
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
                        self::OBJECTS['https://example.com/actor/1']
                    ),
                    $this->makeRequest(
                        'https://example.com/actor/1/inbox',
                        Request::METHOD_POST,
                        '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                        array(
                            'signed' => true,
                            'actor' => TestActivityPubObject::fromArray(
                                self::OBJECTS['https://elsewhere.com/actor/1']
                            ),
                        )
                    )
                ),
            ),
            array(
                'id' => 'basicOutboxTest',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/outbox',
                    Request::METHOD_POST,
                    '{"type": "Create"}',
                    array(
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://example.com/actor/1']
                        ),
                    )
                ),
                'expectedEventName' => OutboxActivityEvent::NAME,
                'expectedEvent' => new OutboxActivityEvent(
                    array( 'type' => 'Create' ),
                    TestActivityPubObject::fromArray(
                        self::OBJECTS['https://example.com/actor/1']
                    ),
                    $this->makeRequest(
                        'https://example.com/actor/1/outbox',
                        Request::METHOD_POST,
                        '{"type": "Create"}',
                        array(
                            'actor' => TestActivityPubObject::fromArray(
                                self::OBJECTS['https://example.com/actor/1']
                            ),
                        )
                    )
                ),
            ),
            array(
                'id' => 'inboxRequestMustBeSigned',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ),
            array(
                'id' => 'outboxRequestsMustBeAuthed',
                'request' => $this->makeRequest(
                    'https://example.com/actor/1/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array()
                ),
                'expectedException' => UnauthorizedHttpException::class,
            ),
            array(
                'id' => '404sIfNotFound',
                'request' => $this->makeRequest(
                    'https://example.com/actor/notreal/inbox',
                    Request::METHOD_POST,
                    '{"type": "Create", "actor": "https://elsewhere.com/actor/1"}',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => NotFoundHttpException::class,
            ),
            array(
                'id' => 'BadRequestIfNoBody',
                'request' => $this->makeRequest(
                    'https://example.com/actor/notreal/inbox',
                    Request::METHOD_POST,
                    '',
                    array(
                        'signed' => true,
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => BadRequestHttpException::class,
            ),
            array(
                'id' => 'BadRequestIfMalformedBody',
                'request' => $this->makeRequest(
                    'https://example.com/actor/notreal/inbox',
                    Request::METHOD_POST,
                    'this is not JSON',
                    array(
                        'signed' => 'true',
                        'actor' => TestActivityPubObject::fromArray(
                            self::OBJECTS['https://elsewhere.com/actor/1']
                        ),
                    )
                ),
                'expectedException' => BadRequestHttpException::class,
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
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->expectException( $testCase['expectedException'] );
            }
            $postController->handle( $request );
        }
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
?>
