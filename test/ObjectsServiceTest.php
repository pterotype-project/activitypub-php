<?php
require_once dirname( __FILE__ ) . '/config/SQLiteTestCase.php';
require_once dirname( __FILE__ ) . '/config/ArrayDataSet.php';
require_once dirname( __FILE__ ) . '/../src/Objects/ObjectsService.php';

use ActivityPub\Config\SQLiteTestCase;
use ActivityPub\Config\ArrayDataSet;
use ActivityPub\Entities\Field;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Database\PrefixNamingStrategy;
use PHPUnit\DbUnit\TestCaseTrait;

class ObjectsServiceTest extends SQLiteTestCase
{

    protected $entityManager;
    protected $objectsService;

    protected function getDataSet()
    {
        return new ArrayDataSet( array( 'objects' => array(), 'fields' => array() ) );
    }

    protected function setUp()
    {
        parent::setUp();
        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/../src/Entities' ), true
        );
        $namingStrategy = new PrefixNamingStrategy( '' );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = array(
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        );
        $this->entityManager = EntityManager::create( $dbParams, $dbConfig );
        $this->objectsService = new ObjectsService( $this->entityManager );
    }

    public function testItCreatesObject()
    {
        $fields = array(
            'id' => 'https://example.com/notes/1',
            'type' => 'Note',
            'content' => 'This is a note',
        );
        $object = $this->objectsService->createObject( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1 )
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
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
        $object = $this->objectsService->createObject( $fields );
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
        $object = $this->objectsService->createObject( $fields );
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
        $object = $this->objectsService->createObject( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1 ),
                array( 'id' => 2 ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 2,
                    'name' => 'id',
                    'value' => 'https://example.com/actors/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 2,
                    'name' => 'type',
                    'value' => 'Person',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 1,
                    'name' => 'attributedTo',
                    'value' => null,
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
        $object = $this->objectsService->createObject( $fields );
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
        $object = $this->objectsService->createObject( $fields );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1 ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/collections/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Collection',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => 'https://example.com/notes/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => 'https://example.com/notes/2',
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
        $object = $this->objectsService->createObject( $fields );
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
        $object = $this->objectsService->createObject( $fields );
        $expected = new ArrayDataSet( array(
             'objects' => array(
                array( 'id' => 1 ),
                array( 'id' => 2 ),
                array( 'id' => 3 ),
            ),
            'fields' => array(
                array(
                    'id' => 1,
                    'object_id' => 1,
                    'name' => 'id',
                    'value' => 'https://example.com/collections/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 2,
                    'object_id' => 1,
                    'name' => 'type',
                    'value' => 'Collection',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 3,
                    'object_id' => 2,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 4,
                    'object_id' => 2,
                    'name' => 'type',
                    'value' => 'Note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 5,
                    'object_id' => 2,
                    'name' => 'content',
                    'value' => 'This is a note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => null,
                    'targetObject_id' => 2,
                ),
array(
                    'id' => 7,
                    'object_id' => 3,
                    'name' => 'id',
                    'value' => 'https://example.com/notes/2',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 8,
                    'object_id' => 3,
                    'name' => 'type',
                    'value' => 'Note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 9,
                    'object_id' => 3,
                    'name' => 'content',
                    'value' => 'This is another note',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 10,
                    'object_id' => 1,
                    'name' => 'items',
                    'value' => null,
                    'targetObject_id' => 3,
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
        $object = $this->objectsService->createObject( $fields );
        $arr = $object->asArray();
        xdebug_break();
        $this->assertEquals( $fields, $arr );
    }
}
?>
