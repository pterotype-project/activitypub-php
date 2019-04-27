<?php

namespace ActivityPub\JsonLd;

use ActivityPub\JsonLd\Exceptions\PropertyNotDefinedException;
use ArrayAccess;
use ML\JsonLD\JsonLD;
use ML\JsonLD\Node;
use ML\JsonLD\Value;

/**
 * Class JsonLdNode
 * @package ActivityPub\JsonLd
 *
 * A representation of a node in a JSON-LD graph. Supports lazy-loading linked nodes and persisting RDF triples to
 * a storage backend.
 */
class JsonLdNode implements ArrayAccess
{
    /**
     * The internal representation of the node.
     * @var Node
     */
    private $node;

    /**
     * The JSON-LD context that should be used when getting/setting properties on this node.
     * @var array|\stdClass|string
     */
    private $context;

    /**
     * JsonLdNode constructor.
     * @param \stdClass $jsonLd The JSON-LD input as a stdClass.
     * @param string $context This node's JSON-LD context.
     */
    public function __construct( $jsonLd, $context )
    {
        $doc = JsonLD::getDocument( $jsonLd );
        $graph = $doc->getGraph();
        $id = empty( $doc->getIri() ) ? '_:b0' : $doc->getIri();
        $this->node = $graph->getNode( $id );
        if ( is_null( $this->node ) ) {
            $this->node = $graph->createNode();
        }
        $this->context = $context;
    }

    /**
     * Cardinality-one get. Gets the single value for the property named $name.
     * If there are multiple values defined for the property, only the first value is returned.
     * @param string $name The property name to get.
     * @return mixed  A single property value.
     * @throws PropertyNotDefinedException If no property named $name exists.
     */
    public function get( $name )
    {
        $property = $this->getNodeProperty( $name );
        if ( is_array( $property ) ) {
            $property = $property[0];
        }
        return $this->resolveProperty( $property );
    }

    /**
     * Cardinality-many get. Gets all the values for the property named $name.
     * If there is only one value defined for the property, it is returned as a length-1 array.
     * @param string $name The property name to get.
     * @return mixed  A single property value.
     * @throws PropertyNotDefinedException If no property named $name exists.
     */
    public function getMany( $name )
    {
        $property = $this->getNodeProperty( $name );
        if ( ! is_array( $property ) ) {
            $property = array( $property );
        }
        return $this->resolveProperty( $property );
    }

    /**
     * A convenience wrapper around $this->get( $name ). Cardinality-one.
     * @param string $name
     * @return mixed
     * @throws PropertyNotDefinedException
     */
    public function __get( $name )
    {
        return $this->get( $name );
    }

    /**
     * Gets the value of the property named $name.
     * @param string $name
     * @return mixed
     * @throws PropertyNotDefinedException if no property named $name exists.
     */
    private function getNodeProperty( $name )
    {
        $expandedName = $this->expand_name( $name );
        $property = $this->node->getProperty( $expandedName );
        if ( is_null( $property ) ) {
            throw new PropertyNotDefinedException( $name );
        }
        return $property;
    }


    /**
     * Resolves the result of $this->node->getProperty() to something the application can use.
     * @param mixed $property
     * @return array|string
     */
    private function resolveProperty( $property )
    {
        if ( $property instanceof Value ) {
            return $property->getValue();
        } else if ( is_array( $property ) ) {
            return array_map( array( $this, 'resolveProperty' ), $property );
        }
        // TODO handle lazy-loading linked nodes here
        // also, figure out what to do about as:items -- the vocab says it should be a node but the JsonLD lib
        // seems to resolve it to an array if it comes in as an array of string values...
    }

    /**
     * Sets the value for a new or existing property on the node.
     * If the property already exists, the new value overwrites the old value(s).
     * @param string $name
     * @param string|\stdClass|array $value
     */
    public function setProperty( $name, $value )
    {
        $expandedName = $this->expand_name( $name );
        if ( $value instanceof \stdClass || is_array( $value ) ) {
            // TODO handle adding a new linked node here
            // should instantiate a new JsonLdNode and recursively call __set
        } else if ( $value instanceof JsonLdNode ) {
            // TODO handle adding a new linked node here
            // by getting the \ML\JsonLD\Node instance from the $value and calling $this->node->addPropertyValue()
        } else {
            $this->node->setProperty( $expandedName, $value );
        }
    }

    /**
     * Convenience wrapper around $this->setProperty().
     * If the property already exists, the new value overwrites the old value(s).
     * @param string $name
     * @param string|\stdClass|array $value
     */
    public function __set( $name, $value )
    {
        return $this->setProperty( $name, $value );
    }

    /**
     * Adds a new value to a new or existing property on the node.
     * If the property already exists, the new value is added onto the existing values rather than
     * overwriting them.
     * @param string $name
     * @param string|\stdClass|array $value
     */
    public function addPropertyValue( $name, $value )
    {
        $expandedName = $this->expand_name( $name );
        if ( $value instanceof \stdClass || is_array( $value ) ) {
            // TODO handle adding a new linked node here
            // should instantiate a new JsonLdNode and recursively call __set
        } else if ( $value instanceof JsonLdNode ) {
            // TODO handle adding a new linked node here
            // by getting the \ML\JsonLD\Node instance from the $value and calling $this->node->addPropertyValue()
        } else {
            $this->node->addPropertyValue( $expandedName, $value );
        }
    }

    /**
     * Clears the property named $name, if it exists.
     * @param string $name
     */
    public function clearProperty( $name )
    {
        return $this->setProperty( $name, null );
    }

    /**
     * Resolves $name to a full IRI given the JSON-LD context of this node.
     * @param string $name The name of the property to resolve.
     * @return string The expanded name.
     */
    private function expand_name( $name )
    {
        $dummyObj = (object) array(
            '@context' => $this->context,
            $name => '_dummyValue',
        );
        $expanded = (array) JsonLD::expand( $dummyObj )[0];
        return array_keys( $expanded )[0];
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists( $offset )
    {
        $expandedName = $this->expand_name( (string) $offset );
        return !is_null( $this->node->getProperty( $expandedName ) );
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     * @throws PropertyNotDefinedException
     */
    public function offsetGet( $offset )
    {
        return $this->get( (string) $offset );
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet( $offset, $value )
    {
        return $this->setProperty( (string) $offset, $value );
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset( $offset )
    {
        return $this->clearProperty( (string) $offset );
    }
}