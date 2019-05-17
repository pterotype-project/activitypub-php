<?php

namespace ActivityPub\Test\TestUtils;

use ActivityPub\Utils\UuidProvider;

class TestUuidProvider extends UuidProvider
{
    /**
     * @var array
     */
    private $uuids;

    /**
     * @var int
     */
    private $uuidIdx;

    /**
     * TestUuidProvider constructor.
     * @param $uuids array
     */
    public function __construct( $uuids )
    {
        $this->uuids = $uuids;
        $this->uuidIdx = 0;
    }

    public function uuid()
    {
        $uuid = $this->uuids[$this->uuidIdx];
        $this->uuidIdx = ( $this->uuidIdx + 1 ) % count( $this->uuids );
        return $uuid;
    }
}