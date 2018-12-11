<?php
namespace ActivityPub\Utils;

/**
 * An interface to provide DateTime objects, so that DateTimes can be fixed in tests
 */
interface DateTimeProvider
{
    /**
     * Returns a DateTime for some context
     *
     * @param string $context The context of the DateTime. Defaults to ''.
     *
     * @return DateTime
     */
    public function getTime( $context = '' );
}
?>
