<?php
namespace ActivityPub\Entities;

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

    public function __construct() {
        $this->fields = new ArrayCollection();
        $this->referencingFields = new ArrayCollection();
    }

    public function asArray() {
        $arr = array();
        foreach ( $this->getFields() as $field ) {
            $key = $field->getName();
            $value = $field->getValueOrTargetObject();
            if ( is_string( $value ) ) {
                $arr[$key] = $value;
            } else if ( $value instanceof ActivityPubObject ) {
                $arr[$key] = $value->asArray();
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

    public function addField(Field $field) {
        $this->fields[] = $field;
    }

    public function addReferencingField(Field $field) {
        $this->referencingFields[] = $field;
    }
}
?>
