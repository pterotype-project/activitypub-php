<?php
namespace ActivityPub\Objects;

class ContextProvider
{
    private $ctx;
    
    public function __construct( $ctx = null )
    {
        if ( ! $ctx ) {
            $ctx = self::getDefaultContext();
        }
        $this->ctx = $ctx;
    }

    public function getContext()
    {
        return $this->ctx;
    }

    public static function getDefaultContext()
    {
        return array(
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
        );
    }
}

