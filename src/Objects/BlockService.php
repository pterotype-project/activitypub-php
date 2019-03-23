<?php

namespace ActivityPub\Objects;

class BlockService
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( ObjectsService $objectsService )
    {
        $this->objectsService = $objectsService;
    }

    /**
     * Returns an array of actorIds that are the object of a block activity
     * by $blockingActorId
     *
     * @param string $blockingActorId
     * @return array
     */
    public function getBlockedActorIds( $blockingActorId )
    {
        $q = array(
            'type' => 'Block',
            'actor' => array(
                'id' => $blockingActorId,
            ),
        );
        $blocks = $this->objectsService->query( $q );
        $blockedIds = array();
        foreach ( $blocks as $block ) {
            if ( $block->hasField( 'object' ) ) {
                $blockedActor = $block['object'];
                if ( is_string( $blockedActor ) ) {
                    $blockedIds[] = $blockedActor;
                } else {
                    $blockedIds[] = $blockedActor['id'];
                }
            }
        }
        return $blockedIds;
    }
}