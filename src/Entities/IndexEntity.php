<?php
namespace ActivityPub\Entities;

/**
 * The index table hold the JSON-LD object graph.
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
 */
class IndexEntity
{
    /**
     * @ManyToOne(targetEntity="ObjectEntity", inversedBy="subjectIndices")
     * @var ObjectEntity The subject of the index row
     */
    protected $subject;
    /**
     * @Column(type="string")
     * @var string The predicate of the index row
     */
    protected $predicate;
    /**
     * @Column(type="string")
     * @var string The value of the index row; mutually exclusive with $object
     */
    protected $value;
    /**
     * @ManyToOne(targetEntity="ObjectEntity", inversedBy="objectIndices")
     * @var ObjectEntity The object of the index row; mutually exclusive with $value
     */
    protected $object;

    /**
     * Create a new index row with a string value
     *
     * @param ObjectEntity $subject The subject of the index row
     * @param string $predicate The predicate of the index row
     * @param string $value The value of the index row
     * @return IndexEntity The new index row
     */
    public static function withValue(ObjectEntity $subject, string $predicate, string $value) {
        $idx = new IndexEntity();
        $idx->setSubject( $subject );
        $idx->setPredicate( $predicate );
        $idx->setValue( $value );
        return $idx;
    }

    /**
     * Create a new index row that references another object
     *
     * @param ObjectEntity $subject The subject of the index row
     * @param string $predicate The predicate of the index row
     * @param ObjectEntity $object The object of the index row
     * @return IndexEntity The new index row
     */
    public static function withObject(ObjectEntity $subject, string $predicate, ObjectEntity $object) {
        $idx = new IndexEntity();
        $idx->setSubject( $subject );
        $idx->setPredicate( $predicate );
        $idx->setObject( $object );
        return $idx;
    }

    protected function setSubject(ObjectEntity $subject) {
        $subject->addSubjectIndex( $this );
        $this->subject = $subject;
    }

    protected function setObject(ObjectEntity $object) {
        $object->addObjectIndex( $this );
        $this->object = $object;
    }

    protected function setPredicate(string $predicate) {
        $this->predicate = $predicate;
    }

    protected function setValue(string $value) {
        $this->value = $value;
    }

    /**
     * Returns the subject of the index row
     *
     * @return ObjectEntity
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * Returns the predicate of the index row
     *
     * @return string
     */
    public function getPredicate() {
        return $this->predicate;
    }

    /**
     * Returns either the value or the object of the index row, depending on which was set
     *
     * @return string|ObjectEntity
     */
    public function getValueOrObject() {
        if ( ! is_null( $this->object ) ) {
            return $this->object;
        } else {
            return $this->value;
        }
    }
}
?>
