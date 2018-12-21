<?php
namespace ActivityPub\Test;

use DateTime;
use ActivityPub\Crypto\RsaKeypair;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Test\Config\ArrayDataSet;
use ActivityPub\Test\Config\SQLiteTestCase;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

class EntityTest extends SQLiteTestCase
{
    protected $entityManager;
    protected $dateTimeProvider;

    protected function getDataSet()
    {
        return new ArrayDataSet( array(
            'objects' => array(),
            'fields' => array(),
            'keys' => array(),
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
        $this->dateTimeProvider = new TestDateTimeProvider(
            new DateTime( "12:00" ), new DateTime( "12:01" )
        );
    }

    private function getTime( $context ) {
        return $this->dateTimeProvider
            ->getTime( $context )
            ->format( "Y-m-d H:i:s" );
    }

    public function testItCreatesAnObjectWithAPrivateKey()
    {
        $object = new ActivityPubObject( $this->dateTimeProvider->getTime( 'create' ) );
        $privateKey = 'a private key';
        $object->setPrivateKey( $privateKey );
        $this->entityManager->persist( $object );
        $this->entityManager->flush();
        $now = $this->getTime( 'create' );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now ),
            ),
            'keys' => array(
                array( 'id' => 1, 'object_id' => 1, 'key' => $privateKey )
            ),
        ) );
        $expectedObjectsTable = $expected->getTable( 'objects' );
        $expectedKeysTable = $expected->getTable( 'keys' );
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $keysQueryTable = $this->getConnection()->createQueryTable(
            'keys', 'SELECT * FROM keys'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedKeysTable, $keysQueryTable );
    }

    public function itUpdatesAPrivateKey()
    {
        $object = new ActivityPubObject( $this->dateTimeProvider->getTime( 'create' ) );
        $privateKey = 'a private key';
        $object->setPrivateKey( $privateKey );
        $this->entityManager->persist( $object );
        $this->entityManager->flush();
        $newPrivateKey = 'a new private key';
        $object->setPrivateKey( $newPrivateKey );
        $this->entityManager->persiste( $object );
        $this->entityManager->flush();
        $now = $this->getTime( 'create' );
        $expected = new ArrayDataSet( array(
            'objects' => array(
                array( 'id' => 1, 'created' => $now, 'lastUpdated' => $now ),
            ),
            'keys' => array(
                array( 'id' => 1, 'object_id' => 1, 'key' => $newPrivateKey )
            ),
        ) );
        $expectedObjectsTable = $expected->getTable( 'objects' );
        $expectedKeysTable = $expected->getTable( 'keys' );
        $objectsQueryTable = $this->getConnection()->createQueryTable(
            'objects', 'SELECT * FROM objects'
        );
        $keysQueryTable = $this->getConnection()->createQueryTable(
            'keys', 'SELECT * FROM keys'
        );
        $this->assertTablesEqual( $expectedObjectsTable, $objectsQueryTable );
        $this->assertTablesEqual( $expectedKeysTable, $keysQueryTable );
    }
}
?>
