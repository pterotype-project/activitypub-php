<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Test\TestUtils;

use ActivityPub\Utils\DateTimeProvider;
use DateTime;

/**
 * A DateTimeProvider that returns fixed values for create and update times
 */
class TestDateTimeProvider implements DateTimeProvider
{
    protected $context;

    /**
     * @param array $context An array mapping context strings to DateTime instances
     */
    public function __construct( $context )
    {
        $this->context = $context;
    }

    public function getTime( $context = '' )
    {
        if ( array_key_exists( $context, $this->context ) ) {
            return $this->context[$context];
        } else {
            return new DateTime( 'now' );
        }
    }
}

