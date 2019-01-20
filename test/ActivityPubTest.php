<?php
namespace ActivityPub\Test;
    
use ActivityPub\ActivityPub;
use ActivityPub\Test\TestConfig\SQLiteTestCase;
use ActivityPub\Test\TestConfig\ArrayDataSet;

class ActivityPubTest extends SQLiteTestCase
{
    public function getDataSet() {
        return new ArrayDataSet( array() );
    }
    
    public function testItCreatesSchema() {
        $this->assertTrue( file_exists( $this->getDbPath() ) );
    }

    /**
     * @depends testItCreatesSchema
     */
    public function testItUpdatesSchema() {
        $activityPub = new ActivityPub(array(
            'dbOptions' => array(
                'driver' => 'pdo_sqlite',
                'path' => $this->getDbPath(),
            ),
        ) );
        $activityPub->updateSchema();
        $this->assertTrue( file_exists( $this->getDbPath() ) );
    }

    protected function getDbPath() {
        return dirname( __FILE__ ) . '/db.sqlite';
    }
}
?>
