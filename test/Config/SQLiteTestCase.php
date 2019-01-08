<?php
namespace ActivityPub\Test\Config;

use ActivityPub\ActivityPub;
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\DbUnit\Operation\Composite;
use PHPUnit\DbUnit\Operation\Factory;

abstract class SQLiteTestCase extends TestCase
{
    use TestCaseTrait;

    private $pdo = null;
    private $conn = null;
    private $dbPath = '';

    protected function setUp()
    {
        parent::setUp();
        $dbPath = $this->getDbPath();
        if ( file_exists( $dbPath ) ) {
            unlink( $dbPath );
        }
        $activityPub = new ActivityPub( array(
            'dbOptions' => array(
                'driver' => 'pdo_sqlite',
                'path' => $dbPath,
            ),
        ) );
        $activityPub->updateSchema();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unlink( $this->getDbPath() );
        unset( $this->conn );
        unset( $this->pdo );
    }

    protected function getDbPath()
    {
        return dirname( __FILE__ ) . '/db.sqlite';
    }

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
}
?>
