<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Utils;

use DateTime;

class SimpleDateTimeProvider implements DateTimeProvider
{
    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns a new DateTime for the given context
     *
     * @param string $context The context. Defaults to ''.
     *
     * @return DateTime
     */
    public function getTime( $context = '' )
    {
        return new DateTime( "now" );
    }
}

