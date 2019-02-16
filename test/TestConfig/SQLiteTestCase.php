<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Test\TestConfig;

use ActivityPub\ActivityPub;
use ActivityPub\Config\ActivityPubConfig;

abstract class SQLiteTestCase extends APTestCase
{
    use \PHPUnit_Extensions_Database_TestCase_Trait;

    private $pdo = null;
    private $conn = null;
    private $dbPath = '';

    final public function getConnection()
    {
        if ( $this->conn === null ) {
            if ( $this->pdo === null ) {
                $this->dbPath = $this->getDbPath();
                $this->pdo = new \PDO( "sqlite:{$this->dbPath}" );
            }
            $this->conn = $this->createDefaultDBConnection( $this->pdo, $this->dbPath );
        }
        return $this->conn;
    }

    protected function getDbPath()
    {
        return dirname( __FILE__ ) . '/db.sqlite';
    }

    protected function setUp()
    {
        parent::setUp();
        $dbPath = $this->getDbPath();
        if ( file_exists( $dbPath ) ) {
            unlink( $dbPath );
        }
        $config = ActivityPubConfig::createBuilder()
            ->setDbConnectionParams( array(
                'driver' => 'pdo_sqlite',
                'path' => $dbPath,
            ) )
            ->build();
        $activityPub = new ActivityPub( $config );
        $activityPub->updateSchema();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unlink( $this->getDbPath() );
        unset( $this->conn );
        unset( $this->pdo );
    }
}

