<?php

namespace ActivityPub\Utils;

use Symfony\Component\HttpFoundation\Request;

class Util
{
    /**
     * Returns true if the input array is associative
     *
     * @param array $arr The array to test
     * @return bool True if the array is associative, false otherwise
     */
    public static function isAssoc( array $arr )
    {
        if ( array() === $arr ) return false;
        return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
    }

    /**
     * Returns true if all of the specified keys exist in the array
     *
     * @param array $arr The array to check
     * @param array $keys The keys to check the existence of
     * @return bool True if all of the keys are in the array, false otherwise
     */
    public static function arrayKeysExist( array $arr, array $keys )
    {
        foreach ( $keys as $key ) {
            if ( !array_key_exists( $key, $arr ) ) {
                return false;
            }
        }
        return true;
    }

    public static function isLocalUri( $uri )
    {
        $request = Request::createFromGlobals();
        return parse_url( $uri, PHP_URL_HOST ) === $request->getHost();
    }
}

