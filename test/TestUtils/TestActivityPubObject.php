<?php
namespace ActivityPub\Test\TestUtils;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Test\TestUtils\TestField;
use DateTime;

/**
 * Like an ActivityPubObject, but with fixed timestamps for testing
 */
class TestActivityPubObject extends ActivityPubObject
{
    public static function getDefaultTime() {
        return DateTime::createFromFormat(
            DateTime::RFC2822, 'Sun, 05 Jan 2014 21:31:40 GMT'
        );
    }

    private $fixedTime;

    public function __construct( DateTime $time = null )
    {
        if ( ! $time ) {
            $time = self::getDefaultTime();
        }
        $this->fixedTime = $time;
        parent::__construct( $time );
    }

    public function addField( Field $field, DateTime $time = null )
    {
        parent::addField( $field, $time );
        $this->lastUpdated = $this->fixedTime;
    }

    public function removeField( Field $field, DateTime $time = null )
    {
        parent::removeField( $field, $time );
        $this->lastUpdated = $this->fixedTime;
    }

    public function setLastUpdated( $lastUpdated )
    {
        // do not change lastUpdated
    }

    public static function fromArray( array $arr, DateTime $time = null )
    {
        if ( ! $time ) {
            $time = self::getDefaultTime();
        }
        $object = new TestActivityPubObject( $time );
        foreach ( $arr as $name => $value ) {
            if ( is_array( $value ) ) {
                $child = self::fromArray( $value, $time );
                TestField::withObject( $object, $name, $child, $time );
            } else {
                TestField::withValue( $object, $name, $value, $time );
            }
        }
        return $object;
    }
}
?>
