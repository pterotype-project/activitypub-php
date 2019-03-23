<?php

namespace ActivityPub\Objects;

use ActivityPub\Entities\ActivityPubObject;

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
        $blockQuery = array(
            'type' => 'Block',
            'actor' => array(
                'id' => $blockingActorId,
            ),
        );
        $blocks = $this->objectsService->query( $blockQuery );

        // TODO this is janky and slow - there's probably a better way
        $undoQuery = array(
            'type' => 'Undo',
            'actor' => array(
                'id' => $blockingActorId,
            ),
            'object' => array(
                'type' => 'Block',
            ),
        );
        $undos = $this->objectsService->query( $undoQuery );
        $undoneBlocks = array();
        foreach ( $undos as $undo ) {
            if ( $undo->hasField( 'object' ) ) {
                $undoObject = $undo['object'];
                if ( is_string( $undoObject ) ) {
                    $undoneBlocks[$undoObject] = 1;
                } else if ( $undoObject instanceof ActivityPubObject && $undoObject->hasField( 'id' ) ) {
                    $undoneBlocks[$undoObject['id']] = 1;
                }
            }
        }

        $blockedIds = array();
        foreach ( $blocks as $block ) {
            if ( array_key_exists( $block['id'], $undoneBlocks ) ) {
                continue;
            }
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