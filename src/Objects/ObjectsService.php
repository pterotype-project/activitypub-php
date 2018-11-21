<?php
namespace ActivityPub\Objects;

use ActivityPub\Entities\ObjectEntity;
use ActivityPub\Entities\IndexEntity;
use Doctrine\ORM\EntityManager;

class ObjectsService
{
    protected $entityManager;

    public function __construct( EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createObject( array $fields )
    {
        // TODO validate object fields?
        $object = new ObjectEntity();
        $this->entityManager->persist( $object );
        // TODO handle objects as well as values
        foreach ( $fields as $field => $value ) {
            $index = IndexEntity::withValue( $object, $field, $value );
            $this->entityManager->persist( $index );
        }
        $this->entityManager->flush();
        return $object;
    }
}
?>
