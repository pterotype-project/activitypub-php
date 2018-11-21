<?php
require_once dirname( __FILE__ ) . '/config/APTestCase.php';
require_once dirname( __FILE__ ) . '/config/ArrayDataSet.php';
require_once dirname( __FILE__ ) . '/../src/Objects/ObjectsService.php';

use ActivityPub\Config\APTestCase;
use ActivityPub\Config\ArrayDataSet;
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
        return new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1 )
            ),
            'indices' => array(
                array(
                    'subject' => 1,
                    'predicate' => 'id',
                    'value' => 'https://example.com/notes/1',
                    'object' => null,
                ),
                array(
                    'subject' => 1,
                    'predicate' => 'type',
                    'value' => 'Note',
                    'object' => null,
                ),
                array(
                    'subject' => 1,
                    'predicate' => 'content',
                    'value' => 'This is a note',
                    'object' => null,
                ),
            ),
        ) );
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
        $objectEntity = $this->objectsService->createObject( $fields );
        $queryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $expectedTable = $this->getDataSet()->getTable('objects');
        $this->assertTablesEqual( $expectedTable, $queryTable );
    }
}
?>
