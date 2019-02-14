<?php
namespace ActivityPub\Test\TestUtils;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use DateTime;

/**
 * Like a Field, but with fixed timestamps for testing
 */
class TestField extends Field
{
    private $fixedTime;

    protected function __construct( $time = null )
    {
        if ( ! $time ) {
            $time = TestActivityPubObject::getDefaultTime();
        }
        parent::__construct( $time );
        $this->fixedTime = $time;
    }

    public function setTargetObject( ActivityPubObject $targetObject, DateTime $time = null )
    {
        parent::setTargetObject( $targetObject, $time );
        $this->lastUpdated = $this->fixedTime;
    }

    public function setValue( $value, DateTime $time = null )
    {
        parent::setValue( $value, $time );
        $this->lastUpdated = $this->fixedTime;
    }

    protected function setCreated( DateTime $timestamp )
    {
        // don't set created
    }

    protected function setLastupdated( DateTime $timestamp )
    {
        // don't set lastUpdated
    }

}

