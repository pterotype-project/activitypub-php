<?php

namespace ActivityPub\JsonLd;

use InvalidArgumentException;

/**
 * A view of a JSON-LD graph. Maps ids to JsonLdNode instances.
 * Class JsonLdGraph
 * @package ActivityPub\JsonLd
 */
class JsonLdGraph
{
    /**
     * @var int
     */
    private $nextBlankId;

    /**
     * @var array
     */
    private $graph;

    public function __construct()
    {
        $this->nextBlankId = 0;
        $this->graph = array();
    }

    public function addNode( JsonLdNode $node )
    {
        $id = $node->getId();
        if ( is_null( $id ) ) {
            $id = $this->getNextBlankId();
            $node->setId( $id );
        }
        $this->graph[$id] = $node;
    }

    public function getNode( $id )
    {
        if ( array_key_exists( $id, $this->graph ) ) {
            return $this->graph[$id];
        }
    }

    public function nameBlankNode( $blankNodeName, $newNodeName ) {
        if ( array_key_exists( $newNodeName, $this->graph ) ) {
            throw new InvalidArgumentException( "$newNodeName is already defined." );
        }
        if ( ! array_key_exists( $blankNodeName, $this->graph ) ) {
            throw new InvalidArgumentException( "$blankNodeName is not in the graph." );
        }
        $this->graph[$newNodeName] = $this->graph[$blankNodeName];
        unset( $this->graph[$blankNodeName] );
    }

    private function getNextBlankId()
    {
        $nextId = $this->nextBlankId;
        $this->nextBlankId += 1;
        return "_:b$nextId";
    }
}