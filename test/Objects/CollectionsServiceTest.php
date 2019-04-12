<?php

namespace ActivityPub\Test\Objects;

use ActivityPub\Auth\AuthService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CollectionsServiceTest extends APTestCase
{
    /**
     * @var CollectionsService
     */
    private $collectionsService;

    /**
     * @var AuthService
     */
    private $authService;

    public function setUp()
    {
        $this->authService = new AuthService();
        $contextProvider = new ContextProvider();
        $httpClient = $this->getMock( Client::class );
        $httpClient->method( 'send' )->willReturn(
            new Psr7Response( 200, array(), json_encode( array(
                'type' => 'OrderedCollectionPage',
                'orderedItems' => array(
                    'item3',
                    'item4',
                ),
            ) ) )
        );
        $entityManager = $this->getMock( EntityManager::class );
        $objectsService = $this->getMock( ObjectsService::class );
        $this->collectionsService = new CollectionsService(
            4,
            $this->authService,
            $contextProvider,
            $httpClient,
            new SimpleDateTimeProvider(),
            $entityManager,
            $objectsService
        );
    }

    public function provideTestCollectionPaging()
    {
        return array(
            array( array(
                'id' => 'lessThanOnePage',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1',
                    Request::METHOD_GET
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=desc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/4',
                            ),
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                            array(
                                'id' => 'https://example.com/objects/2',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'moreThanOnePage',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1',
                    Request::METHOD_GET
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=desc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'next' => 'https://example.com/objects/1?offset=4&sort=desc',
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/6',
                            ),
                            array(
                                'id' => 'https://example.com/objects/5',
                            ),
                            array(
                                'id' => 'https://example.com/objects/4',
                            ),
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'notFirstPage',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1?offset=3',
                    Request::METHOD_GET
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1?offset=3&sort=desc',
                    'type' => 'OrderedCollectionPage',
                    'partOf' => 'https://example.com/objects/1',
                    'startIndex' => 3,
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'authFilteringPublic',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                            'to' => 'https://example.com/actors/1',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                            'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                            'to' => 'https://example.com/actors/2',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1',
                    Request::METHOD_GET
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=desc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/5',
                            ),
                            array(
                                'id' => 'https://example.com/objects/4',
                                'to' => 'https://www.w3.org/ns/activitystreams#Public',
                            ),
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'authFilteringSpecificActor',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                            'to' => 'https://example.com/actors/1',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                            'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                            'to' => 'https://example.com/actors/2',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1',
                    Request::METHOD_GET
                ),
                'requestAttributes' => array(
                    'actor' => 'https://example.com/actors/2',
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=desc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/6',
                                'to' => 'https://example.com/actors/2',
                            ),
                            array(
                                'id' => 'https://example.com/objects/5',
                            ),
                            array(
                                'id' => 'https://example.com/objects/4',
                                'to' => 'https://www.w3.org/ns/activitystreams#Public',
                            ),
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'sortAsc',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1?sort=asc',
                    Request::METHOD_GET
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=asc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'next' => 'https://example.com/objects/1?offset=4&sort=asc',
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/2',
                            ),
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                            array(
                                'id' => 'https://example.com/objects/4',
                            ),
                            array(
                                'id' => 'https://example.com/objects/5',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'authFilteringSpecificActorSortAsc',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                            'to' => 'https://example.com/actors/1',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                            'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        ),
                        array(
                            'id' => 'https://example.com/objects/5',
                        ),
                        array(
                            'id' => 'https://example.com/objects/6',
                            'to' => 'https://example.com/actors/2',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1?sort=asc',
                    Request::METHOD_GET
                ),
                'requestAttributes' => array(
                    'actor' => 'https://example.com/actors/2',
                ),
                'expectedResult' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'first' => array(
                        '@context' => array(
                            'https://www.w3.org/ns/activitystreams',
                            'https://w3id.org/security/v1',
                        ),
                        'id' => 'https://example.com/objects/1?offset=0&sort=asc',
                        'type' => 'OrderedCollectionPage',
                        'partOf' => 'https://example.com/objects/1',
                        'startIndex' => 0,
                        'orderedItems' => array(
                            array(
                                'id' => 'https://example.com/objects/3',
                            ),
                            array(
                                'id' => 'https://example.com/objects/4',
                                'to' => 'https://www.w3.org/ns/activitystreams#Public',
                            ),
                            array(
                                'id' => 'https://example.com/objects/5',
                            ),
                            array(
                                'id' => 'https://example.com/objects/6',
                                'to' => 'https://example.com/actors/2',
                            ),
                        ),
                    ),
                ),
            ) ),
            array( array(
                'id' => 'nonExistentPage',
                'collection' => array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.com/objects/1',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'https://example.com/objects/2',
                        ),
                        array(
                            'id' => 'https://example.com/objects/3',
                        ),
                        array(
                            'id' => 'https://example.com/objects/4',
                        ),
                    )
                ),
                'request' => Request::create(
                    'https://example.com/objects/1?offset=3',
                    Request::METHOD_GET
                ),
                'expectedException' => NotFoundHttpException::class,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestCollectionPaging
     */
    public function testCollectionPaging( $testCase )
    {
        $this->authService = new AuthService();
        $contextProvider = new ContextProvider();
        $httpClient = $this->getMock( Client::class );
        $httpClient->method( 'send' )->willReturn(
            new Psr7Response( 200, array(), json_encode( array(
                'type' => 'OrderedCollectionPage',
                'orderedItems' => array(
                    'item3',
                    'item4',
                ),
            ) ) )
        );
        $entityManager = $this->getMock( EntityManager::class );
        $collection = $testCase['collection'];
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'update' )->willReturn( TestActivityPubObject::fromArray( $collection ) );
        $this->collectionsService = new CollectionsService(
            4,
            $this->authService,
            $contextProvider,
            $httpClient,
            new SimpleDateTimeProvider(),
            $entityManager,
            $objectsService
        );
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $request = $testCase['request'];
        if ( array_key_exists( 'requestAttributes', $testCase ) ) {
            $request->attributes->add( $testCase['requestAttributes'] );
        }
        $actual = $this->collectionsService->pageAndFilterCollection(
            $testCase['request'],
            TestActivityPubObject::fromArray( $testCase['collection'] ),
            function( ActivityPubObject $item ) use ( $testCase ) {
                return $this->authService->isAuthorized( $testCase['request'], $item );
            }
        );
        $this->assertEquals(
            $testCase['expectedResult'], $actual, "Error on test $testCase[id]"
        );
    }

    public function provideTestCollectionNormalizing()
    {
        return array(
            array( array(
                'id' => 'basicNormalizingTest',
                'collection' => array(
                    'type' => 'Collection',
                    'first' => array(
                        'type' => 'CollectionPage',
                        'items' => array(
                            'item1',
                            'item2',
                        ),
                    ),
                ),
                'expectedResult' => array(
                    'type' => 'Collection',
                    'items' => array(
                        'item1',
                        'item2',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'orderedNormalizingTest',
                'collection' => array(
                    'type' => 'OrderedCollection',
                    'first' => array(
                        'type' => 'OrderedCollectionPage',
                        'orderedItems' => array(
                            'item1',
                            'item2',
                        ),
                    ),
                ),
                'expectedResult' => array(
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        'item1',
                        'item2',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'pageTraversal',
                'collection' => array(
                    'type' => 'OrderedCollection',
                    'first' => array(
                        'type' => 'OrderedCollectionPage',
                        'orderedItems' => array(
                            'item1',
                            'item2',
                        ),
                        'next' => array(
                            'type' => 'OrderedCollectionPage',
                            'orderedItems' => array(
                                'item3',
                                'item4',
                            ),
                        ),
                    ),
                ),
                'expectedResult' => array(
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        'item1',
                        'item2',
                        'item3',
                        'item4',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'pageTraversal',
                'collection' => array(
                    'type' => 'OrderedCollection',
                    'first' => array(
                        'type' => 'OrderedCollectionPage',
                        'orderedItems' => array(
                            'item1',
                            'item2',
                        ),
                        'next' => 'https://example.com/collection/1?page=2',
                    ),
                ),
                'expectedResult' => array(
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        'item1',
                        'item2',
                        'item3',
                        'item4',
                    ),
                ),
            ) ),
        );
    }

    /**
     * @dataProvider provideTestCollectionNormalizing
     */
    public function testCollectionNormalizing( $testCase )
    {
        $collection = $testCase['collection'];
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $actual = $this->collectionsService->normalizeCollection( $collection );
        $this->assertEquals(
            $testCase['expectedResult'], $actual, "Error on test $testCase[id]"
        );
    }
}

