<?php
namespace ActivityPub\Utils;

class RandomProvider
{
    /**
     * Generates a random alphanumeric string of length $length
     *
     * NOT cryptographically secure, but good enough for ids
     * @param int $length The length of the random string to generate
     * @return string
     */
    public function randomString( $length )
    {
        $str = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $str = $str . '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'[rand( 0, 61 )];
        }
        return $str;
    }
}

