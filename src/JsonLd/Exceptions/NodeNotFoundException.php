<?php

namespace ActivityPub\JsonLd\Exceptions;

use Exception;

class NodeNotFoundException extends Exception
{
    public function __construct( $iri, $previous = null )
    {
        $message = "Node $iri not found.";
        parent::__construct( $message, 0, $previous );
    }
}