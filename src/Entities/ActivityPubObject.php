<?php
namespace ActivityPub\Entities;

use ArrayAccess;
use BadMethodCallException;
use DateTime;
use ActivityPub\Utils\Util;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents an ActivityPub JSON-LD object
 * @Entity @Table(name="objects")
 */
class ActivityPubObject implements ArrayAccess
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * This object's fields
     * @OneToMany(targetEntity="Field", mappedBy="object", cascade={"persist", "remove"})
     * @var Field[] An ArrayCollection of Fields
     */
    protected $fields;

    /**
     * Fields which reference this object
     * @OneToMany(targetEntity="Field", mappedBy="targetObject")
     * @var Field[] An ArrayCollection of Fields
     */
    protected $referencingFields;

    /**
     * The object's creation timestamp
     * @Column(type="datetime")
     * @var DateTime
     */
    protected $created;

    /**
     * The object's last update timestamp
     * @Column(type="datetime")
     * @var DateTime
     */
    protected $lastUpdated;

    /**
     * The private key associated with this object, if any
     * @OneToOne(targetEntity="PrivateKey", mappedBy="object", cascade={"all"})
     * @var PrivateKey
     */
    protected $privateKey;

    public function __construct( DateTime $time = null ) {
        if ( ! $time ) {
            $time = new DateTime( "now" );
        }
        $this->fields = new ArrayCollection();
        $this->referencingFields = new ArrayCollection();
        $this->created = $time;
        $this->lastUpdated = $time;
    }

    /**
     * Returns the object represented as an array
     *
     * @param int $depth The depth at which child objects will be collapsed to ids
     *
     * @return array|string Either the object or its id if $depth is < 0
     */
    public function asArray( int $depth = 1 ) {
        if ( $depth < 0 && $this->hasField( 'id' ) ) {
            return $this->getFieldValue( 'id' );
        }
        $arr = array();
        foreach ( $this->getFields() as $field ) {
            $key = $field->getName();
            if ( $field->hasValue() ) {
                $arr[$key] = $field->getValue();
            } else if ( $field->hasTargetObject() ) {
                $arr[$key] = $field->getTargetObject()->asArray( $depth - 1 );
            }
        }
        return $arr;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the object's fields
     *
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns the fields which reference this object
     *
     * @return Field[]
     */
    public function getReferencingFields()
    {
        return $this->referencingFields;
    }

    /**
     * Returns the object's creation timestamp
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Returns the object's last updated timestamp
     *
     * @return DateTime
     */
    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    /**
     * Returns true if the object contains a field with key $name
     *
     * @return boolean
     */
    public function hasField( string $name )
    {
        foreach( $this->getFields() as $field ) {
            if ( $field->getName() === $name ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the fields named $field, if it exists
     *
     * @param string $name The name of the field to get
     * @return Field|null
     */
    public function getField( string $name )
    {
        foreach( $this->getFields() as $field ) {
            if ( $field->getName() === $name ) {
                return $field;
            }
        }
    }

    /**
     * Returns the value of the field with key $name
     *
     * The value is either a string, another ActivityPubObject, or null
     *   if no such key exists.
     *
     * @return string|ActivityPubObject|null The field's value, or null if
     *   the field is not found
     */
    public function getFieldValue( string $name )
    {
        foreach( $this->getFields() as $field ) {
            if ( $field->getName() === $name ) {
                return $field->getValueOrTargetObject();
            }
        }
        return null;
    }

    /**
     * Adds a new field on the object
     *
     * Don't call this directly; instead, use one of the
     *   Field constructors and pass in this object as the
     *   $object.
     *
     * @param Field $field
     */
    public function addField( Field $field, DateTime $time = null )
    {
        if ( ! $time ) {
            $time = new DateTime( "now" );
        }
        $this->fields[] = $field;
        $this->lastUpdated = $time;
    }

    /**
     * Adds a new field that references this object
     *
     * Don't call this directly; instead, use one of the
     *   Field constructors and pass in this object as the
     *   $targetObject.
     *
     * @param Field $field
     */
    public function addReferencingField( Field $field )
    {
        $this->referencingFields[] = $field;
    }

    /**
     * Removes a referencing field
     *
     * Don't call this directly; instead, set the field'd
     *   targetObject to something else using $field->setTargetObject()
     *   or $field->setValue().
     *
     * @param Field $field
     */
    public function removeReferencingField( Field $field )
    {
        $this->referencingFields->removeElement( $field );
    }

    /**
     * Removes a field from the object
     * @param Field $field The field to remove
     *
     */
    public function removeField( Field $field, DateTime $time = null )
    {
        if ( ! $time ) {
            $time = new DateTime( "now" );
        }
        $this->fields->removeElement( $field );
        $this->lastUpdated = $time;
    }

    /**
     * Sets the last updated timestamp
     *
     * @param DateTime $lastUpdated The new last updated timestamp
     *
     */
    public function setLastUpdated( $lastUpdated )
    {
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * Returns true if this object has an associated private key, false if otherwise
     *
     * @return bool
     */
    public function hasPrivateKey()
    {
        return $this->privateKey !== null;
    }

    /**
     * Sets the object's private key
     *
     * @param string $key The new private key value
     */
    public function setPrivateKey( string $key )
    {
        if ( $this->hasPrivateKey() ) {
            $this->privateKey->setKey( $key );
        } else {
            $this->privateKey = new PrivateKey( $key, $this );
        }
    }

    public function offsetExists( $offset )
    {
        return $this->hasField( $offset );
    }

    public function offsetGet( $offset )
    {
        return $this->getFieldValue( $offset );
    }

    public function offsetSet( $offset, $value )
    {
        throw new BadMethodCallException(
            'ActivityPubObject fields cannot be directly set'
        );
    }

    public function offsetUnset( $offset )
    {
        throw new BadMethodCallException(
            'ActivityPubObject fields cannot be directly unset'
        );
    }

    /**
     * Returns true if $other has all the same fields as $this
     *
     * @param ActivityPubObject $other The other object to compare to
     * @return bool Whether or not this object has the same fields and values as 
     *   the other
     */
    public function equals( ActivityPubObject $other )
    {
        foreach( $other->getFields() as $otherField ) {
            $thisField = $this->getField( $otherField->getName() );
            if ( ! $thisField ) {
                return false;
            }
            if ( ! $thisField->equals( $otherField ) ) {
                return false;
            }
        }
        return true;
    }
}
?>
