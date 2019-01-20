<?php
namespace ActivityPub\Objects;

class ContextProvider
{
    const DEFAULT_CONTEXT = array(
        'https://www.w3.org/ns/activitystreams',
        'https://w3id.org/security/v1',
    );
    
    private $ctx;
    
    public function __construct( $ctx = null )
    {
        if ( ! $ctx ) {
            $ctx = self::DEFAULT_CONTEXT;
        }
        $this->ctx = $ctx;
    }

    public function getContext()
    {
        return $this->ctx;
    }
}
?>
