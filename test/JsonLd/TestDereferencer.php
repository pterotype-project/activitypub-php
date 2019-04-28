<?php

namespace ActivityPub\Test\JsonLd;

use ActivityPub\JsonLd\Dereferencer\DereferencerInterface;
use ActivityPub\JsonLd\Exceptions\NodeNotFoundException;
use stdClass;

class TestDereferencer implements DereferencerInterface
{
    private $nodes;

    public function __construct( $nodes )
    {
        $this->nodes = $nodes;
    }

    /**
     * @param string $iri The IRI to dereference.
     * @return stdClass|array The dereferenced node.
     * @throws NodeNotFoundException If a node with the IRI could not be found.
     */
    public function dereference( $iri )
    {
        if ( array_key_exists( $iri, $this->nodes ) ) {
            return $this->nodes[$iri];
        } else {
            throw new NodeNotFoundException( $iri );
        }
    }
}