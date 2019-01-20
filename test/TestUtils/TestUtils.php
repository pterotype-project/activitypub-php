<?php
namespace ActivityPub\Test\TestUtils;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;

class TestUtils
{
    public static function objectFromArray( $array ) {
        $object = new ActivityPubObject();
        foreach ( $array as $name => $value ) {
            if ( is_array( $value ) ) {
                $child = self::objectFromArray( $value );
                Field::withObject( $object, $name, $child );
            } else {
                Field::withValue( $object, $name, $value );
            }
        }
        return $object;
    }
}
?>
