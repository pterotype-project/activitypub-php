<?php

namespace ActivityPub\JsonLd;

use ActivityPub\JsonLd\Dereferencer\DereferencerInterface;

/**
 * A factory class for constructing JsonLdNode instances
 * Class JsonLdNodeFactory
 * @package ActivityPub\JsonLd
 */
class JsonLdNodeFactory
{
    /**
     * The JSON-LD context to give to new JsonLdNode instances.
     * @var array|\stdClass|string
     */
    private $context;

    /**
     * The dereferencer to pass to new JsonLdNode instances.
     * @var DereferencerInterface
     */
    private $dereferencer;

    public function __construct( $context, DereferencerInterface $dereferencer )
    {
        $this->context = $context;
        $this->dereferencer = $dereferencer;
    }

    /**
     * Construct and return a new JsonLdNode.
     * @param Node|\stdClass $jsonLd The JSON-LD object input
     * @return JsonLdNode
     */
    public function newNode( $jsonLd )
    {
        return new JsonLdNode( $jsonLd, $this->context, $this, $this->dereferencer );
    }
}