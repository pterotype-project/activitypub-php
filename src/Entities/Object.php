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

    /**
     * @var string
     * @Column(type="string")
     */
    protected $json;

    public function getId()
    {
        return $this->id;
    }

    public function getJson()
    {
        return $this->json;
    }

    public function setJson($json)
    {
        $this->json = $json;
    }
}
?>
