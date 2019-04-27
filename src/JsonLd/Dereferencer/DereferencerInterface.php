<?php


namespace ActivityPub\JsonLd\Dereferencer;


use ActivityPub\JsonLd\Exceptions\NodeNotFoundException;
use stdClass;

interface DereferencerInterface
{
    /**
     * @param string $iri The IRI to dereference.
     * @return stdClass|array The dereferenced node.
     * @throws NodeNotFoundException If a node with the IRI could not be found.
     */
    public function dereference( $iri );
}