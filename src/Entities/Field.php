<?php /** @noinspection PhpDocMissingThrowsInspection */

/** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Entities;

use DateTime;

/**
 * The field table hold the JSON-LD object graph.
 *
 * Its structure is based on https://changelog.com/posts/graph-databases-101:
 * Every row has a subject, which is a foreign key into the Objects table,
 * a predicate, which is a the JSON field that describes the graph edge relationship
 * (e.g. 'id', 'inReplyTo', 'attributedTo'), and either a value or an object.
 * A value is a string that represents the value of the JSON-LD field if the field
 * is a static value, like { "url": "https://example.com" }. An object is another foreign
 * key into the objects table that represents the value of the JSON-LD field if the
 * field is another JSON-LD object, like { "inReplyTo": { <another object } }.
 * A subject can have multiple values for the same predicate - this represents a JSON-LD
 * array.
 *
 * @Entity @Table(name="fields")
 */
class Field
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="ActivityPubObject", inversedBy="fields")
     * @var ActivityPubObject The object to which this field belongs
     */
    protected $object;
    /**
     * @Column(type="string")
     * @var string The name of the field
     */
    protected $name;
    /**
     * If this is set, this is a leaf node in the object graph.
     *
     * @Column(type="string", nullable=true)
     * @var string The value of the field; mutually exclusive with $targetObject
     */
    protected $value;
    /**
     * @ManyToOne(targetEntity="ActivityPubObject", inversedBy="referencingFields")
     * @var ActivityPubObject The value of the field if it holds another object;
     *   mutually exclusive with $value
     */
    protected $targetObject;

    /**
     * The field's creation timestamp
     * @Column(type="datetime")
     * @var DateTime The creation timestamp
     */
    protected $created;

    /**
     * The field's last updated timestamp
     * @Column(type="datetime")
     * @var DateTime The last updated timestamp
     */
    protected $lastUpdated;

    protected function __construct( DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $this->created = $time;
        $this->lastUpdated = $time;
    }

    /**
     * Create a new field with a string value
     *
     * @param ActivityPubObject $object The object to which this field belongs
     * @param string $name The name of the field
     * @param string $value The value of the field
     * @param DateTime|null $time
     * @return Field The new field
     * @throws \Exception
     */
    public static function withValue( ActivityPubObject $object, $name, $value, DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $field = new Field( $time );
        $field->setObject( $object, $time );
        $field->setName( $name );
        $field->setValue( $value, $time );
        return $field;
    }

    /**
     * Create a new field that holds another object
     *
     * @param ActivityPubObject $object The object to which this field belongs
     * @param string $name The name of the field
     * @param ActivityPubObject $targetObject The object that this field holds
     * @param DateTime|null $time
     * @return Field The new field
     * @throws \Exception
     */
    public static function withObject( ActivityPubObject $object,
                                       $name,
                                       ActivityPubObject $targetObject,
                                       DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $field = new Field( $time );
        $field->setObject( $object, $time );
        $field->setName( $name );
        $field->setTargetObject( $targetObject, $time );
        return $field;
    }

    /**
     * Returns the object to which this field belongs
     *
     * @return ActivityPubObject
     */
    public function getObject()
    {
        return $this->object;
    }

    protected function setObject( ActivityPubObject $object, DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $object->addField( $this, $time );
        $this->object = $object;
    }

    /**
     * Returns the value or the target object of the field, depending on which was set
     *
     * @return string|ActivityPubObject
     */
    public function getValueOrTargetObject()
    {
        if ( !is_null( $this->targetObject ) ) {
            return $this->targetObject;
        } else {
            return $this->value;
        }
    }

    /**
     * Returns the field's creation timestamp
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    protected function setCreated( DateTime $timestamp )
    {
        $this->created = $timestamp;
    }

    /**
     * Returns the field's last updated timestamp
     *
     * @return DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    protected function setLastUpdated( DateTime $timestamp )
    {
        $this->lastUpdated = $timestamp;
    }

    /**
     * Returns true if $this is equal to $other
     *
     * @param Field $other
     * @return bool
     */
    public function equals( Field $other )
    {
        if ( $this->getName() !== $other->getName() ) {
            return false;
        }
        if ( $this->hasValue() ) {
            return $other->hasValue() && $other->getValue() === $this->getValue();
        } else {
            return $other->hasTargetObject() &&
                $this->getTargetObject()->equals( $other->getTargetObject() );
        }
    }

    /**
     * Returns the name of the field
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    protected function setName( $name )
    {
        $this->name = $name;
    }

    /**
     * Returns true if the field has a value
     *
     * @return bool
     */
    public function hasValue()
    {
        return $this->value !== null;
    }

    /**
     * Returns the value of the field or null if there isn't one
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue( $value, DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $oldTargetObject = $this->getTargetObject();
        if ( $oldTargetObject ) {
            $oldTargetObject->removeReferencingField( $this );
        }
        $this->targetObject = null;
        $this->value = $value;
        $this->lastUpdated = $time;
    }

    /**
     * Returns true if the field has a target object
     *
     * @return bool
     */
    public function hasTargetObject()
    {
        return $this->targetObject !== null;
    }

    /**
     * Returns the target object of the field or null if there isn't one
     *
     * @return ActivityPubObject|null
     */
    public function getTargetObject()
    {
        return $this->targetObject;
    }

    public function setTargetObject( ActivityPubObject $targetObject, DateTime $time = null )
    {
        if ( !$time ) {
            $time = new DateTime( "now" );
        }
        $this->value = null;
        $oldTargetObject = $this->getTargetObject();
        if ( $oldTargetObject ) {
            $oldTargetObject->removeReferencingField( $this );
        }
        $targetObject->addReferencingField( $this );
        $this->targetObject = $targetObject;
        $this->lastUpdated = $time;
    }
}

