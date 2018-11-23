<?php
namespace ActivityPub\Config;

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
        $dbPath = dirname( __FILE__ ) . '/../db.sqlite';
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
        unlink( dirname( __FILE__ ) . '/../db.sqlite' );
        unset( $this->conn );
        unset( $this->pdo );
    }

    final public function getConnection()
    {
        if ( $this->conn === null ) {
            if ( $this->pdo === null ) {
                $this->dbPath = dirname( __FILE__ ) . '/../db.sqlite';
                $this->pdo = new \PDO( "sqlite:{$this->dbPath}" );
            }
            $this->conn = $this->createDefaultDBConnection( $this->pdo, $this->dbPath );
        }
        return $this->conn;
    }
}
?>
