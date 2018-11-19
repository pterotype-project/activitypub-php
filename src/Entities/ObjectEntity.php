<?php
namespace ActivityPub\Entities;

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

    public function getId()
    {
        return $this->id;
    }
}
?>
