<?php
namespace ActivityPub\Utils;

use DateTime;
use ActivityPub\Utils\DateTimeProvider;

class SimpleDateTimeProvider implements DateTimeProvider
{
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
?>
