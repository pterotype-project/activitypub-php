<?php
namespace ActivityPub\Test\TestUtils;

use DateTime;
use ActivityPub\Utils\DateTimeProvider;

/**
 * A DateTimeProvider that returns fixed values for create and update times
 */
class TestDateTimeProvider implements DateTimeProvider
{
    protected $createTime;
    protected $updateTime;

    public function __construct( DateTime $createTime, DateTime $updateTime )
    {
        $this->createTime = $createTime;
        $this->updateTime = $updateTime;
    }
    
    public function getTime( $context = '' )
    {
        if ( $context === 'create' ) {
            return $this->createTime;
        } else if ( $context === 'update' ) {
            return $this->updateTime;
        } else {
            return new DateTime( 'now' );
        }
    }
}
?>
