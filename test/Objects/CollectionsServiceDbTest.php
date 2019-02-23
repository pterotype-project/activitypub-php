<?php
namespace ActivityPub\Test\Objects;


use ActivityPub\Auth\AuthService;
use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\ArrayDataSet;
use ActivityPub\Test\TestConfig\SQLiteTestCase;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use ActivityPub\Utils\DateTimeProvider;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

class CollectionsServiceDbTest extends SQLiteTestCase
{

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var CollectionsService
     */
    private $collectionsService;

    public function setUp()
    {
        parent::setUp();
        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/../../src/Entities' ), true
        );
        $namingStrategy = new PrefixNamingStrategy( '' );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = array(
            'driver' => 'pdo_sqlite',
            'path' => $this->getDbPath(),
        );
        $this->entityManager = EntityManager::create( $dbParams, $dbConfig );
        $this->dateTimeProvider = new TestDateTimeProvider( array(
            'objects-service.create' => new DateTime( "12:00" ),
            'objects-service.update' => new DateTime( "12:01" ),
            'collections-service.add' => new DateTime( "12:03" ),
        ) );
        $this->httpClient = $this->getMock( Client::class );
        $this->httpClient->method( 'send' )
            ->willReturn( new Response( 404 ) );
        $this->objectsService = new ObjectsService(
            $this->entityManager, $this->dateTimeProvider, $this->httpClient
        );
        $this->collectionsService = new CollectionsService(4, new AuthService(), new ContextProvider(),
            $this->httpClient, $this->dateTimeProvider, $this->entityManager, $this->objectsService);
    }

    private function getTime( $context )
    {
        return $this->dateTimeProvider
            ->getTime( $context )
            ->format( "Y-m-d H:i:s" );
    }

    public function testAddItem()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'collection' => array(
                    'id' => 'https://example.com/collections/1',
                    'type' => 'Collection',
                    'items' => array(),
                ),
                'item' => array(
                    'id' => 'https://example.com/notes/1',
                    'type' => 'Note',
                ),
                'expectedDataSet' => array(
                    'objects' => array(
                        array(
                            'id' => 1,
                            'created' => $this->getTime('objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 2,
                            'created' => $this->getTime('objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 3,
                            'created' => $this->getTime('collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                    ),
                    'fields' => array(
                        array(
                            'id' => 1,
                            'object_id' => 1,
                            'name' => 'id',
                            'value' => 'https://example.com/collections/1',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                         array(
                            'id' => 2,
                            'object_id' => 1,
                            'name' => 'type',
                            'value' => 'Collection',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 3,
                            'object_id' => 1,
                            'name' => 'items',
                            'value' => null,
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => 2,
                        ),
                        array(
                            'id' => 4,
                            'object_id' => 3,
                            'name' => 'id',
                            'value' => 'https://example.com/notes/1',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 5,
                            'object_id' => 3,
                            'name' => 'type',
                            'value' => 'Note',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 6,
                            'object_id' => 2,
                            'name' => '0',
                            'value' => null,
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => 3,
                        ),
                        array(
                            'id' => 7,
                            'object_id' => 1,
                            'name' => 'totalItems',
                            'value' => '1',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                    ),
                ),
            ),
            array(
                'id' => 'createItemsField',
                'collection' => array(
                    'id' => 'https://example.com/collections/1',
                    'type' => 'Collection',
                ),
                'item' => array(
                    'id' => 'https://example.com/notes/1',
                    'type' => 'Note',
                ),
                'expectedDataSet' => array(
                    'objects' => array(
                        array(
                            'id' => 1,
                            'created' => $this->getTime('objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 2,
                            'created' => $this->getTime('collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 3,
                            'created' => $this->getTime('collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                    ),
                    'fields' => array(
                        array(
                            'id' => 1,
                            'object_id' => 1,
                            'name' => 'id',
                            'value' => 'https://example.com/collections/1',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 2,
                            'object_id' => 1,
                            'name' => 'type',
                            'value' => 'Collection',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 3,
                            'object_id' => 1,
                            'name' => 'items',
                            'value' => null,
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => 2,
                        ),
                        array(
                            'id' => 4,
                            'object_id' => 3,
                            'name' => 'id',
                            'value' => 'https://example.com/notes/1',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 5,
                            'object_id' => 3,
                            'name' => 'type',
                            'value' => 'Note',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 6,
                            'object_id' => 2,
                            'name' => '0',
                            'value' => null,
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => 3,
                        ),
                        array(
                            'id' => 7,
                            'object_id' => 1,
                            'name' => 'totalItems',
                            'value' => '1',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                    ),
                ),
            ),
            array(
                'id' => 'existingItems',
                'collection' => array(
                    'id' => 'https://example.com/collections/1',
                    'type' => 'Collection',
                    'items' => array(
                        array(
                            'id' => 'https://example.com/activities/1'
                        )
                    ),
                ),
                'item' => array(
                    'id' => 'https://example.com/notes/1',
                    'type' => 'Note',
                ),
                'expectedDataSet' => array(
                    'objects' => array(
                        array(
                            'id' => 1,
                            'created' => $this->getTime('objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 2,
                            'created' => $this->getTime('objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                        array(
                            'id' => 3,
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                        ),
                        array(
                            'id' => 4,
                            'created' => $this->getTime('collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                        ),
                    ),
                    'fields' => array(
                        array(
                            'id' => 1,
                            'object_id' => 1,
                            'name' => 'id',
                            'value' => 'https://example.com/collections/1',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 2,
                            'object_id' => 1,
                            'name' => 'type',
                            'value' => 'Collection',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 3,
                            'object_id' => 3,
                            'name' => 'id',
                            'value' => 'https://example.com/activities/1',
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 4,
                            'object_id' => 2,
                            'name' => '0',
                            'value' => null,
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => 3,
                        ),
                        array(
                            'id' => 5,
                            'object_id' => 1,
                            'name' => 'items',
                            'value' => null,
                            'created' => $this->getTime( 'objects-service.create' ),
                            'lastUpdated' => $this->getTime( 'objects-service.create' ),
                            'targetObject_id' => 2,
                        ),
                        array(
                            'id' => 6,
                            'object_id' => 4,
                            'name' => 'id',
                            'value' => 'https://example.com/notes/1',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 7,
                            'object_id' => 4,
                            'name' => 'type',
                            'value' => 'Note',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                        array(
                            'id' => 8,
                            'object_id' => 2,
                            'name' => '1',
                            'value' => null,
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => 4,
                        ),
                        array(
                            'id' => 9,
                            'object_id' => 1,
                            'name' => 'totalItems',
                            'value' => '2',
                            'created' => $this->getTime( 'collections-service.add' ),
                            'lastUpdated' => $this->getTime( 'collections-service.add' ),
                            'targetObject_id' => null,
                        ),
                    ),
                ),
            ),
        );
        foreach ( $testCases as $testCase )
        {
            self::setUp();
            $collection = $this->objectsService->persist( $testCase['collection'] );
            $this->collectionsService->addItem( $collection, $testCase['item'] );
            $expectedDataSet = new ArrayDataSet( $testCase['expectedDataSet'] );
            $expectedObjects = $expectedDataSet->getTable( 'objects' );
            $expectedFields = $expectedDataSet->getTable( 'fields' );
            $actualObjects = $this->getConnection()->createQueryTable(
                'objects', 'SELECT * FROM objects'
            );
            $actualFields = $this->getConnection()->createQueryTable(
                'fields', 'SELECT * FROM fields'
            );
            $this->assertTablesEqual( $expectedObjects, $actualObjects, "Error on test $testCase[id]");
            $this->assertTablesEqual( $expectedFields, $actualFields, "Error on test $testCase[id]");
            self::tearDown();
        }
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return new ArrayDataSet( array( 'objects' => array(), 'fields' => array() ) );
    }
}