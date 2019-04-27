<?php

namespace ActivityPub\JsonLd\Exceptions;

use Exception;
use Throwable;

/**
 * This exception is thrown on an attempt to access an undefined property on a JsonLdNode.
 * Class PropertyNotDefinedException
 * @package ActivityPub\JsonLd\Exceptions
 */
class PropertyNotDefinedException extends Exception
{
    /**
     * PropertyNotDefinedException constructor.
     * @param string $name The name of the undefined property.
     * @param Throwable|null $previous
     */
    public function __construct( $name, Throwable $previous = null )
    {
        $message = "Property $name is not defined.";
        parent::__construct( $message, 0, $previous );
    }
}