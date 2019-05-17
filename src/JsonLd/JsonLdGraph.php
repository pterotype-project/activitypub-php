<?php

namespace ActivityPub\JsonLd;

use ActivityPub\Utils\UuidProvider;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * A view of a JSON-LD graph. Maps ids to JsonLdNode instances.
 * Class JsonLdGraph
 * @package ActivityPub\JsonLd
 */
class JsonLdGraph
{
    /**
     * @var array
     */
    private $graph;

    /**
     * @var UuidProvider
     */
    private $uuidProvider;

    public function __construct( UuidProvider $uuidProvider )
    {
        $this->graph = array();
        $this->uuidProvider = $uuidProvider;
    }

    public function addNode( JsonLdNode $node )
    {
        $id = $node->getId();
        if ( is_null( $id ) ) {
            $id = $this->uuidProvider->uuid();
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
}