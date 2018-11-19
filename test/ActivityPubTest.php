<?php
require_once dirname( __FILE__ ) . '/Config/APTestCase.php';
require_once dirname( __FILE__ ) . '/Config/ArrayDataSet.php';
    
use ActivityPub\Config\APTestCase;
use ActivityPub\Config\ArrayDataSet;

class ActivityPubTest extends APTestCase
{
    public function getDataSet() {
        return new ArrayDataSet( array() );
    }
    
    public function testItCreatesSchema() {
        $this->assertTrue( file_exists( $this->dbPath ) );
    }
}
?>
