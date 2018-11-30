<?php
namespace ActivityPub\Entities;

use DateTime;
use ActivityPub\Utils\Util;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents an ActivityPub JSON-LD object
 * @Entity @Table(name="objects")
 */
class ActivityPubObject
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
     * @OneToMany(targetEntity="Field", mappedBy="object")
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

    public function __construct() {
        $this->fields = new ArrayCollection();
        $this->referencingFields = new ArrayCollection();
        $this->created = new DateTime("now");
        $this->lastUpdated = new DateTime("now");
    }

    /**
     * Returns the object represented as an array
     *
     * @return array
     */
    public function asArray() {
        $arr = array();
        foreach ( $this->getFields() as $field ) {
            $key = $field->getName();
            $value = $field->getValueOrTargetObject();
            if ( is_string( $value ) ) {
                self::addItemToObjectArray( $arr, $key, $value );
            } else if ( $value instanceof ActivityPubObject ) {
                self::addItemToObjectArray( $arr, $key, $value->asArray() );
            }
        }
        return $arr;
    }

    /**
     * Adds an item to the array representing this object
     *
     * If the key already exists in the array, the value becomes an array with the
     *   new item appended. Otherwise, the value is just the item.
     *
     * @param array $arr The array (modified by this function)
     * @param string $key The key for the new item
     * @param string|array $item The new item
     */
    private static function addItemToObjectArray( &$arr, $key, $item ) {
        // TODO here's the problem: how can I tell the difference between
        // a field that's an array with one object, or a field that is not
        // an array?
        if ( array_key_exists( $key, $arr ) ) {
            $existing= $arr[$key];
            if ( is_array( $existing ) && ! Util::isAssoc( $existing ) ) {
                $arr[$key][] = $item;
            } else {
                $arr[$key] = array( $existing, $item );
            }
        } else {
            $arr[$key] = $item;
        }
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
     * Adds a new field on the object
     *
     * Don't call this directly; instead, use one of the
     *   Field constructors and pass in this object as the
     *   $object.
     *
     * @param Field $field
     */
    public function addField( Field $field )
    {
        $this->fields[] = $field;
        $this->lastUpdated = new DateTime( "now" );
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
     * Removes a field from the object
     * @param Field $field The field to remove
     *
     */
    public function removeField( Field $field )
    {
        $this->fields->removeElement( $field );
        $this->lastUpdated = new DateTime( "now" );
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
}
?>
