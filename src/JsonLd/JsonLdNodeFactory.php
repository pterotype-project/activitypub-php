<?php

namespace ActivityPub\JsonLd;

use ActivityPub\JsonLd\Dereferencer\DereferencerInterface;
use ActivityPub\JsonLd\TripleStore\TypedRdfTriple;
use ActivityPub\Utils\UuidProvider;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use stdClass;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UuidProvider
     */
    private $uuidProvider;

    public function __construct( $context,
                                 DereferencerInterface $dereferencer,
                                 LoggerInterface $logger,
                                 UuidProvider $uuidProvider )
    {
        $this->context = $context;
        $this->dereferencer = $dereferencer;
        $this->logger = $logger;
        $this->uuidProvider = $uuidProvider;
    }

    /**
     * Construct and return a new JsonLdNode.
     * @param Node|\stdClass $jsonLd The JSON-LD object input.
     * @param JsonLdGraph|null $graph The JSON-LD graph.
     * @param array $backreferences Backreferences to instantiate the new node with.
     * @return JsonLdNode
     */
    public function newNode( $jsonLd, $graph = null, $backreferences = array() )
    {
        if ( is_null( $graph ) ) {
            $graph = new JsonLdGraph( $this->uuidProvider );
        }
        return new JsonLdNode(
            $jsonLd, $this->context, $this, $this->dereferencer, $this->logger, $graph, $backreferences
        );
    }

    /**
     * Constructs a JsonLdNode from a collection of RdfTriples, properly setting up the graph traversals based
     * on relationships between the passed-in triples.
     *
     * @param TypedRdfTriple[] $triples The triples.
     * @param string $rootNodeId The ID of the root node - that is, the node returned from this function. This is
     *                           necessary because the RDF triples array can contain triples from multiple nodes.
     * @param JsonLdGraph|null $graph An existing JsonLdGraph to add this node to.
     */
    public function nodeFromRdf( $triples, $rootNodeId, $graph = null )
    {
        if ( is_null( $graph ) ) {
            $graph = new JsonLdGraph( $this->uuidProvider );
        }
        $buckets = array();
        $backreferences = array();
        foreach ( $triples as $triple ) {
            $buckets[$triple->getSubject()][] = $triple;
        }
        if ( ! array_key_exists( $rootNodeId, $buckets ) ) {
            throw new InvalidArgumentException("No triple with subject $rootNodeId was found");
        }
        $nodes = array();
        foreach ( $buckets as $id => $triples ) {
            $obj = new stdClass();
            foreach( $triples as $triple ) {
                if ( $triple->getObjectType() && $triple->getObjectType() === '@id' ) {
                    $obj->$triple->getPredicate()[] = (object) array( '@id' => $triple->getObject() );
                    $backreferences[$triple->getObject()][] = (object) array(
                        'predicate' => $triple->getPredicate(),
                        'referer' => $triple->getSubject(),
                    );
                } else if ( $triple->getObjectType() ) {
                    $obj->$triple->getPredicate()[] = (object) array(
                        '@type' => $triple->getObjectType(),
                        '@value' => $triple->getObject(),
                    );
                } else {
                    $obj->$triple->getPredicate()[] = (object) array( '@value' => $triple->getObject() );
                }
            }
            $node = $this->newNode( $obj, $graph );
            $nodes[$node->getId()] = $node;
        }
        foreach ( $backreferences as $referencedId => $references ) {
            $referencedNode = $nodes[$referencedId];
            foreach ( $references as $reference ) {
                $referencedNode->addBackReference( $reference->predicate, $nodes[$reference->referer] );
            }
        }
        return $nodes[$rootNodeId];
    }
}