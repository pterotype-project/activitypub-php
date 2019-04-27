<?php

namespace ActivityPub\JsonLd;

use ActivityPub\JsonLd\Dereferencer\DereferencerInterface;
use ActivityPub\JsonLd\Exceptions\PropertyNotDefinedException;
use ArrayAccess;
use InvalidArgumentException;
use ML\JsonLD\Graph;
use ML\JsonLD\JsonLD;
use ML\JsonLD\Node;
use ML\JsonLD\Value;
use stdClass;

/**
 * Class JsonLdNode
 * @package ActivityPub\JsonLd
 *
 * A representation of a node in a JSON-LD graph. Supports lazy-loading linked nodes.
 */
class JsonLdNode implements ArrayAccess
{
    /**
     * The Node within $this->graph that represents this JsonLdNode.
     * @var Node
     */
    private $node;

    /**
     * The portion of the JSON-LD graph that this node knows about.
     * @var Graph
     */
    private $graph;

    /**
     * The factory used to construct this node.
     * @var JsonLdNodeFactory
     */
    private $factory;

    /**
     * The JSON-LD context that should be used when getting/setting properties on this node.
     * @var array|\stdClass|string
     */
    private $context;

    /**
     * The dereferencer, used to dereference foreign nodes based on their IRIs.
     * @var DereferencerInterface
     */
    private $dereferencer;

    /**
     * JsonLdNode constructor.
     * @param Node|\stdClass $jsonLd The JSON-LD input as a stdClass or an existing \ML\JsonLD\Node instance.
     * @param string $context This node's JSON-LD context.
     * @param DereferencerInterface $dereferencer
     */
    public function __construct( $jsonLd, $context, JsonLdNodeFactory $factory, DereferencerInterface $dereferencer )
    {
        $this->factory = $factory;
        $this->dereferencer = $dereferencer;
        $this->context = $context;
        if ( $jsonLd instanceof Node ) {
            $this->node = $jsonLd;
            $this->graph = $jsonLd->getGraph();
        } else {
            $doc = JsonLD::getDocument( $jsonLd );
            $this->graph = $doc->getGraph();
            $nodes = $this->graph->getNodes();
            $this->node = count( $nodes ) > 0 ? $nodes[0] : $this->graph->createNode();
        }
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
        } else if ( $property instanceof Node ) {
            if ( count( $property->getProperties() ) > 0 ) {
                return $this->factory->newNode( $property );
            } else {
                // dereference the node to get its properties, then update $property's props with the retrieved values
                $dereferenced = $this->dereferencer->dereference( $property->getId() );
                $newNode = JsonLD::getDocument( $dereferenced )->getGraph()->getNode( $property->getId() );
                foreach ( $newNode->getProperties() as $name => $value ) {
                    $property->setProperty( $name, $value );
                }
                return $this->factory->newNode( $property );
            }
        }
        // TODO figure out what to do about as:items -- the vocab says it should be a node but the JsonLD lib
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
        if ( is_array( $value ) ) {
            $this->clearProperty( $expandedName );
            foreach ( $value as $v ) {
                $this->addPropertyValue( $expandedName, $v );
            }
        } else if ( $value instanceof stdClass ) {
            $newDoc = JsonLD::getDocument( $value );
            $newNodes = $newDoc->getGraph()->getNodes();
            $newNode = count( $newNodes ) > 0 ? $newNodes[0] : $this->graph->createNode();
            $this->node->setProperty( $expandedName, $newNode );
        } else if ( $value instanceof JsonLdNode ) {
            $this->setProperty( $expandedName, $value->asObject() );
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
     * @param string|stdClass $value
     */
    public function addPropertyValue( $name, $value )
    {
        $expandedName = $this->expand_name( $name );
        if ( is_array( $value ) ) {
            $err = "Can't add array value to a property. To add multiple values call addPropertyValue multiple times or use setProperty";
            throw new InvalidArgumentException( $err );
        } else if ( $value instanceof stdClass ) {
            $newDoc = JsonLD::getDocument( $value );
            $newNodes = $newDoc->getGraph()->getNodes();
            $newNode = count( $newNodes ) > 0 ? $newNodes[0] : $this->graph->createNode();
            $this->node->addPropertyValue( $expandedName, $newNode );
        } else if ( $value instanceof JsonLdNode ) {
            $this->addPropertyValue( $expandedName, $value->asObject() );
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

    public function asObject()
    {
        return $this->node->toJsonLd();
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