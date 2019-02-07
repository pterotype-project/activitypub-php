<?php
namespace ActivityPub\Utils;

class RandomProvider
{
    const ID_CHARS = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const ID_CHARS_LEN = 62;

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
            $str = $str . self::ID_CHARS[rand( 0, self::ID_CHARS_LEN - 1)];
        }
        return $str;
    }
}

