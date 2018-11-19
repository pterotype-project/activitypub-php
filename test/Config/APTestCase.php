<?php
namespace ActivityPub\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

abstract class APTestCase extends TestCase
{
    use TestCaseTrait;

    private static $pdo = null;
    private $conn = null;
    private $dbPath = '';

    final public function getConnection() {
        if ( $this->conn === null ) {
            if ( self::$pdo === null ) {
                $this->dbPath = dirname( __FILE__ ) . '../db.sqlite';
                self::$pdo = new \PDO( "sqlite:{$this->dbPath}" );
            }
            $this->conn = $this->createDefaultConnection( self::$pdo, $this->dbPath );
        }
        return $this->conn;
    }
}
?>
