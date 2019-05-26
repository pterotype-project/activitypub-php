<?php

namespace ActivityPub\JsonLd\TripleStore;

/**
 * A triple represents a single fact in an RDF graph. A triple is made up a subject, a predicate, and an object.
 * The object can also have a type, e.g. "@id" for references to other resources or
 * "http://www.w3.org/2001/XMLSchema#dateTime" for date-time values.
 *
 * See https://www.w3.org/TR/rdf11-concepts/#data-model.
 *
 * Class Triple
 * @package ActivityPub\JsonLd\TripleStore
 */
class TypedRdfTriple
{
    /**
     * @var string|null
     */
    private $subject;


    /**
     * @var string|null
     */
    private $predicate;

    /**
     * @var string|null
     */
    private $object;

    /**
     * @var string|null
     */
    private $objectType;

    private function __construct( $subject = null, $predicate = null, $object = null, $objectType = null )
    {
        $this->subject = $subject;
        $this->predicate = $predicate;
        $this->object = $object;
        $this->objectType = $objectType;
    }

    public static function create( $subject = null, $predicate = null, $object = null, $objectType = null )
    {
        return new TypedRdfTriple( $subject, $predicate, $object, $objectType );
    }

    /**
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return TypedRdfTriple
     */
    public function setSubject( $subject )
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPredicate()
    {
        return $this->predicate;
    }

    /**
     * @param string $predicate
     * @return TypedRdfTriple
     */
    public function setPredicate( $predicate )
    {
        $this->predicate = $predicate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $object
     * @return TypedRdfTriple
     */
    public function setObject( $object )
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     * @return TypedRdfTriple
     */
    public function setObjectType( $objectType )
    {
        $this->objectType = $objectType;
        return $this;
    }

    /**
     * True if this triple has a subject, a predicate, and an object.
     * @return bool
     */
    public function isFullySpecified()
    {
        return $this->getSubject() && $this->getPredicate() && $this->getObject();
    }
}