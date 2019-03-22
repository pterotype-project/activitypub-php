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

    public function getBlockedActorIds()
    {
        // TODO implement me
    }
}