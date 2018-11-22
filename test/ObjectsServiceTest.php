<?php
require_once dirname( __FILE__ ) . '/config/APTestCase.php';
require_once dirname( __FILE__ ) . '/config/ArrayDataSet.php';
require_once dirname( __FILE__ ) . '/../src/Objects/ObjectsService.php';

use ActivityPub\Config\APTestCase;
use ActivityPub\Config\ArrayDataSet;
use ActivityPub\Entities\Field;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Database\PrefixNamingStrategy;
use PHPUnit\DbUnit\TestCaseTrait;

class ObjectsServiceTest extends APTestCase
{
    use TestCaseTrait;

    protected $entityManager;
    protected $objectsService;

    protected function getDataSet()
    {
        return new ArrayDataSet( array() );
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
                    'object_id' => 1,
                    'name' => 'attributedTo',
                    'value' => null,
                    'targetObject_id' => 2,
                ),
                array(
                    'id' => 5,
                    'object_id' => 2,
                    'name' => 'id',
                    'value' => 'https://example.com/actors/1',
                    'targetObject_id' => null,
                ),
                array(
                    'id' => 6,
                    'object_id' => 2,
                    'name' => 'type',
                    'value' => 'Person',
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
}
?>
