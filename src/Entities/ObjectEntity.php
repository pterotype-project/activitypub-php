<?php
namespace ActivityPub\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents an ActivityPub JSON-LD object
 * @Entity @Table(name="objects")
 */
class ObjectEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * Indices for which this object is the subject
     * @OneToMany(targetEntity="IndexEntity", mappedBy="subject")
     * @var IndexEntity[] An ArrayCollection of IndexEntities
     */
    protected $subjectIndices;

    /**
     * Indices for which this object is the object
     * @OneToMany(targetEntity="IndexEntity", mappedBy="object")
     * @var IndexEntity[] An ArrayCollection of IndexEntities
     */
    protected $objectIndices;

    public function __construct() {
        $this->subjectIndex = new ArrayCollection();
        $this->objectIndex = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addSubjectIndex(IndexEntity $idx) {
        $this->subjectIndices[] = $idx;
    }

    public function addObjectIndex(IndexEntity $idx) {
        $this->objectIndices[] = $idx;
    }
}
?>
