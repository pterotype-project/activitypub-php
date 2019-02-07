<?php
namespace ActivityPub\Test\Objects;

use DateTime;
use BadMethodCallException;
use ActivityPub\Test\TestConfig\SQLiteTestCase;
use ActivityPub\Test\TestConfig\ArrayDataSet;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use Doctrine\ORM\EntityManager;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ObjectsServiceTest extends SQLiteTestCase
{
    protected $entityManager;
    protected $objectsService;
    protected $dateTimeProvider;
    protected $httpClient;

    protected function getDataSet()
    {
        return new ArrayDataSet( array( 'objects' => array(), 'fields' => array() ) );
    }

    protected function setUp()
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
        ) );
        $this->httpClient = $this->getMock( Client::class );
        $this->httpClient->method( 'send' )
            ->willReturn( new Response( 404 ) );
        $this->objectsService = new ObjectsService(
            $this->entityManager, $this->dateTimeProvider, $this->httpClient
        );
    }

    private function getTime( $context ) {
        return $this->dateTimeProvider
            ->getTime( $context )
            ->format( "Y-m-d H:i:s" );
    }

    public function testItCreatesObject()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now )
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testObjectFieldsSet()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $object = $this->objectsService->persist( $fields );
        $this->assertCount( 3, $object->getFields() );
        $this->assertEmpty( $object->getReferencingFields() );
    }

    public function testSerializesToArray()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $fields, $object->asArray() );
    }

    public function testDeSerOneItemArrays()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array( "https://example.com/items/1" ),
        );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $fields, $object->asArray() );
    }

    public function testDbStartsEmpty()
    {
        $this->assertSame( 0, $this->getConnection()->getRowCount( 'objects' ) );
        $this->assertSame( 0, $this->getConnection()->getRowCount( 'fields' ) );
    }

    public function testItCreatesNestedObjects()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actors/1',
                'type' => 'Person',
            ),
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now ),
                array( 'id' => 2, 'created' => $now, 'lastUpdated' => $now ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 2,
                    'name' => 'id',
                    'value' => 'https://example.com/actors/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 2,
                    'name' => 'type',
                    'value' => 'Person',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 1,
                    'name' => 'attributedTo',
                    'value' => null,
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => 2,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItSerializesNestedObjects()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actors/1',
                'type' => 'Person',
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $fields, $object->asArray() );
    }

    public function testItCreatesObjectsWithArray()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array(
                "https://example.com/notes/1",
                "https://example.com/notes/2",
            ),
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now ),
                array( 'id' => 2, 'created' => $now, 'lastUpdated' => $now ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/collections/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Collection',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 2,
                    'name' => '0',
                    'value' => 'https://example.com/notes/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 2,
                    'name' => '1',
                    'value' => 'https://example.com/notes/2',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => null,
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => 2,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItSerializesArrayFields()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array(
                "https://example.com/notes/1",
                "https://example.com/notes/2",
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $fields, $object->asArray() );
    }

    public function testItCreatesNestedObjectArrayFields()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array(
                array(
                    'id' => 'https://example.com/notes/1',
                    'type' => 'Note',
                    'content' => 'This is a note',
                ),
                array(
                    'id' => 'https://example.com/notes/2',
                    'type' => 'Note',
                    'content' => 'This is another note',
                ),
            ),
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $expected = new ArrayDataSet( array(
             'objects' => array(
                 array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now ),
                 array( 'id' => 2, 'created' => $now, 'lastUpdated' => $now ),
                 array( 'id' => 3, 'created' => $now, 'lastUpdated' => $now ),
                 array( 'id' => 4, 'created' => $now, 'lastUpdated' => $now ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/collections/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Collection',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 3,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 3,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 3,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 2,
                    'name' => '0',
                    'value' => null,
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => 3,
                ),
                array(
                    'id' => 7,
                    'object_id' => 4,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/2',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 8,
                    'object_id' => 4,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 9,
                    'object_id' => 4,
                    'name' => 'content',
                    'value' => 'This is another note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 10,
                    'object_id' => 2,
                    'name' => '1',
                    'value' => null,
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => 4,
                ),
                array(
                    'id' => 11,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => null,
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => 2,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItSerializesNestedObjectsInArrays()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array(
                array(
                    'id' => 'https://example.com/notes/1',
                    'type' => 'Note',
                    'content' => 'This is a note',
                ),
                array(
                    'id' => 'https://example.com/notes/2',
                    'type' => 'Note',
                    'content' => 'This is another note',
                ),
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $arr = $object->asArray( 2 );
        $this->assertEquals( $fields, $arr );
    }

    public function testItQueriesByFieldValue()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $object = $this->objectsService->persist( $fields );
        $query = array(
            'type' => 'Note',
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 1, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $object, $results[0] );
    }

    public function testDeSerOneItemArrayQuery()
    {
        $fields = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array( "https://example.com/items/1" ),
        );
        $object = $this->objectsService->persist( $fields );
        $query = array(
            'id' => 'https://example.com/collections/1'
        );
        $results = $this->objectsService->query( $query );
        $this->assertEquals( $fields, $results[0]->asArray() );
    }

    public function testItFindsMultipleQueryResults()
    {
        $fieldsOne = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $objectOne = $this->objectsService->persist( $fieldsOne );
        $fieldsTwo = array(
            'id' => 'https://example.com/notes/2',
            'type' => 'Note',
            'content' => 'This is another note',
        );
        $objectTwo = $this->objectsService->persist( $fieldsTwo );
        $query = array(
            'type' => 'Note',
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 2, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $objectOne, $results[0] );
        $this->assertEquals( $objectTwo, $results[1] );
    }

    public function testItFindsObjectByMultipleFields()
    {
        $fieldsOne = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $objectOne = $this->objectsService->persist( $fieldsOne );
        $fieldsTwo = array(
            'id' => 'https://example.com/notes/2',
            'type' => 'Note',
            'content' => 'This is another note',
        );
        $objectTwo = $this->objectsService->persist( $fieldsTwo );
        $query = array(
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 1, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $objectOne, $results[0] );
        $this->assertNotContains( $objectTwo, $results );
    }

    public function testItFindsNestedObjectQueryResults()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actors/1',
                'type' => 'Person',
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $query = array(
            'attributedTo' => array(
                'id' => 'https://example.com/actors/1',
            ),
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 1, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $object, $results[0] );
    }

    public function testNestedSequentialArrayQueryResults()
    {
        $fieldsOne = array(
            'id' => 'https://example.com/collections/1',
            'type' => 'Collection',
            'items' => array(
                'https://example.com/objects/1',
                array( 'id' => 'example.com/objects/2' ),
                'https://example.com/objects/3'
            ),
        );
        $fieldsTwo = array(
            'id' => 'https://example.com/collections/2',
            'type' => 'Collection',
            'items' => array(
                'https://example.com/objects/1',
                array( 'id' => 'example.com/objects/2' ),
            ),
        );
        $objectOne = $this->objectsService->persist( $fieldsOne );
        $objectTwo = $this->objectsService->persist( $fieldsTwo );
        $query = array(
            'items' => array(
                'https://example.com/objects/1',
                array( 'id' => 'example.com/objects/2' ),
                'https://example.com/objects/3'
            ),
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 1, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $objectOne, $results[0] );
        $this->assertEquals( $fieldsOne, $results[0]->asArray( 2 ) );
        $this->assertNotContains( $objectTwo, $results );
    }

    public function testMultiNestedSequentialObjectQueryResults()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actors/2',
                'type' => 'Person',
                'following' => array(
                    'id' => 'https://example.com/collections/1',
                    'type' => 'Collection',
                    'items' => array(
                        array( 'id' => 'https://example.com/actors/1' ),
                    ),
                ),
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $query = array(
            'attributedTo' => array(
                'following' => array(
                    'items' => array(
                        array( 'id' => 'https://example.com/actors/1' )
                    ),
                ),
            ),
        );
        $results = $this->objectsService->query( $query );
        $this->assertCount( 1, $results );
        $this->assertContainsOnlyInstancesOf( ActivityPubObject::class, $results );
        $this->assertEquals( $object, $results[0] );
        $this->assertEquals( $fields, $results[0]->asArray( 4 ) );
    }

    public function testItReturnsEmptyArrayForNoMatches()
    {
        $fields = array(
            'id' => 'https://example.com/note/1',
            'type' => 'Note',
            'content' => 'This is a note'
        );
        $object = $this->objectsService->persist( $fields );
        $query = array( 'type' => 'Article' );
        $result = $this->objectsService->query( $query );
        $this->assertEmpty( $result );
        $this->assertNotContains( $object, $result );
    }

    public function testItDoesNotStoreObjectsWithTheSameId()
    {
         $fieldsOne = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
         );
         $fieldsTwo = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is another note',
        );
        $now = $this->getTime( 'objects-service.create' );
        $objectOne = $this->objectsService->persist( $fieldsOne );
        $objectTwo = $this->objectsService->persist( $fieldsTwo );
        $this->assertEquals( $objectOne, $objectTwo );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now )
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $now,
                    'lastUpdated' => $now,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItGetsById()
    {
        $fields = array(
            'id' => 'https://example.com/note/1',
            'type' => 'Note',
            'content' => 'This is a note'
        );
        $object = $this->objectsService->persist( $fields );
        $found = $this->objectsService->dereference( 'https://example.com/note/1' );
        $this->assertNotNull( $found );
        $this->assertEquals( $object, $found );
    }

    public function testItReturnsNullIfIdNotFound()
    {
        $fields = array(
            'id' => 'https://example.com/note/1',
            'type' => 'Note',
            'content' => 'This is a note'
        );
        $object = $this->objectsService->persist( $fields );
        $found = $this->objectsService->dereference( 'https://example.com/note/2' );
        $this->assertNull( $found );
    }

    public function testItUpdatesObject()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note'
        );
        $createTime = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $update = array( 'content' => 'This note has been updated' );
        $updateTime = $this->getTime( 'objects-service.update' );
        $this->objectsService->update( 'https://example.com/notes/1', $update );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array(
                    'id' => 1,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime
                ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This note has been updated',
                    'created' => $createTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItUpdatesObjectFieldToNewObject()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actors/1',
            ),
        );
        $createTime = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $update = array( 'attributedTo' => array(
            'id' => 'https://example.com/actors/2',
        ) );
        $updateTime = $this->getTime( 'objects-service.update' );
        $this->objectsService->update( 'https://example.com/notes/1', $update );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array(
                    'id' => 1,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime
                ),
                array(
                    'id' => 2,
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                ),
                array(
                    'id' => 3,
                    'created' => $updateTime,
                    'lastUpdated' => $updateTime,
                ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 2,
                    'name' => 'id',
                    'value' => 'https://example.com/actors/1',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 1,
                    'name' => 'attributedTo',
                    'value' => null,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => 3,
                ),
                array(
                    'id' => 6,
                    'object_id' => 3,
                    'name' => 'id',
                    'value' => 'https://example.com/actors/2',
                    'created' => $updateTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItUpdatesObjectFieldArray()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'likes' => array(
                'https://example.com/likes/1',
                'https://example.com/likes/2',
            ),
        );
        $createTime = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $update = array( 'likes' => array(
            'https://example.com/likes/3',
            'https://example.com/likes/4',
        ) );
        $updateTime = $this->getTime( 'objects-service.update' );
        $this->objectsService->update( 'https://example.com/notes/1', $update );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array(
                    'id' => 1,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime
                ),
                array(
                    'id' => 3,
                    'created' => $updateTime,
                    'lastUpdated' => $updateTime,
                ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 1,
                    'name' => 'likes',
                    'value' => null,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => 3,
                ),
                array(
                    'id' => 7,
                    'object_id' => 3,
                    'name' => '0',
                    'value' => 'https://example.com/likes/3',
                    'created' => $updateTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 8,
                    'object_id' => 3,
                    'name' => '1',
                    'value' => 'https://example.com/likes/4',
                    'created' => $updateTime,
                    'lastUpdated' => $updateTime,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testItDeletesObjectField()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note'
        );
        $createTime = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $update = array( 'content' => null );
        $updateTime = $this->getTime( 'objects-service.update' );
        $this->objectsService->update( 'https://example.com/notes/1', $update );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array(
                    'id' => 1,
                    'created' => $createTime,
                    'lastUpdated' => $updateTime
                ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'created' => $createTime,
                    'lastUpdated' => $createTime,
                    'targetObject_id' => null,
                ),
            ),
        ) );
        $expectedObjectsTable = $expected->getTable('objects');
        $expectedFieldsTable = $expected->getTable('fields');
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $fieldsQueryTable = $this->getConnection()->createQueryTable(
            'fields', 'SELECT * FROM fields'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedFieldsTable, $fieldsQueryTable );
    }

    public function testObjectArrayAccess()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $object['content'], 'This is a note' );
        $this->assertNull( $object['attributedTo'] );
    }

    public function testItThrowsTryingToSetObjectFieldLikeArray()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $this->setExpectedException( BadMethodCallException::class );
        $object['content'] = 'This should break';
    }

    public function testItThrowsTryingToUnsetObjectFieldLikeArray()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $this->setExpectedException( BadMethodCallException::class );
        unset( $object['content'] );
    }

    public function testNestedObjectArrayAccess()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
            'attributedTo' => array(
                'id' => 'https://example.com/actor/1'
            ),
        );
        $now = $this->getTime( 'objects-service.create' );
        $object = $this->objectsService->persist( $fields );
        $this->assertEquals( $object['content'], 'This is a note' );
        $this->assertInstanceOf( ActivityPubObject::class, $object['attributedTo'] );
        $this->assertEquals(
            $object['attributedTo']['id'], 'https://example.com/actor/1'
        );
    }

    public function testItHandlesPersistingCircularReferences()
    {
        $fields = array(
            'id' => 'https://example.com/actors/1',
            'type' => 'Person',
            'publicKey' => array(
                'id' => 'https://example.com/actors/1/key',
                'owner' => 'https://example.com/actors/1',
                'publicKeyPem' => 'thePublicKey',
            ),
        );
        $object = $this->objectsService->persist( $fields );
        $expected = array(
            'id' => 'https://example.com/actors/1',
            'type' => 'Person',
            'publicKey' => array(
                'id' => 'https://example.com/actors/1/key',
                'owner' => array(
                    'id' => 'https://example.com/actors/1',
                    'type' => 'Person',
                    'publicKey' => array(
                        'id' => 'https://example.com/actors/1/key',
                        'owner' => 'https://example.com/actors/1',
                        'publicKeyPem' => 'thePublicKey',
                    ),
                ),
                'publicKeyPem' => 'thePublicKey',
            ),
        );
        $this->assertEquals( $expected, $object->asArray( 3 ) );
    }

    public function testReplace()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'object' => array(
                    'id' => 'https://example.com/objects/1',
                    'type' => 'Article',
                    'summary' => 'An article',
                ),
                'replacementId' => 'https://example.com/objects/1',
                'replacement' => array(
                    'id' => 'https://example.com/objects/1',
                    'type' => 'Note',
                    'contents' => 'This is a note',
                ),
                'expectedObject' => array(
                    'id' => 'https://example.com/objects/1',
                    'type' => 'Note',
                    'contents' => 'This is a note',
                ),
            ),
            array(
                'id' => 'itDeletesOrphanedNodes',
                'object' => array(
                    'id' => 'https://example.com/objects/2',
                    'type' => 'Article',
                    'attributedTo' => array(
                        'id' => 'https://example.com/actors/1'
                    ),
                    'foo' => array(
                        'bar' => 'baz',
                    ),
                ),
                'replacementId' => 'https://example.com/objects/2',
                'replacement' => array(
                    'id' => 'https://example.com/objects/2',
                    'type' => 'Article',
                    'attributedTo' => 'https://example.com/actors/1',
                ),
                'expectedObject' => array(
                    'id' => 'https://example.com/objects/2',
                    'type' => 'Article',
                    'attributedTo' => 'https://example.com/actors/1',
                ),
                'expectedQueryResults' => array(
                    array(
                        'query' => array( 'id' => 'https://example.com/actors/1' ),
                        'expectedResult' => array(
                            array( 'id' => 'https://example.com/actors/1' )
                        ),
                    ),
                    array(
                        'query' => array( 'bar' => 'baz' ),
                        'expectedResult' => array(),
                    ),
                ),
            ),
        );
        foreach ( $testCases as $testCase ) {
            $this->setUp();
            $this->objectsService->persist( $testCase['object'] );
            $replacement = $this->objectsService->replace(
                $testCase['replacementId'],
                $testCase['replacement']
            );
            $this->assertEquals(
                $testCase['expectedObject'],
                $replacement->asArray(),
                "Error on test $testCase[id]"
            );
            if ( array_key_exists( 'expectedQueryResults', $testCase ) ) {
                foreach ( $testCase['expectedQueryResults'] as $expectedQueryResult ) {
                    $result = array_map(
                        function( $obj ) { return $obj->asArray(); },
                        $this->objectsService->query( $expectedQueryResult['query'] )
                    );
               }
            }
        }
    }
}









