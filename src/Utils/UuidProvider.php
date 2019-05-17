<?php

namespace ActivityPub\Utils;

use Ramsey\Uuid\Uuid;

class UuidProvider
{
    /**
     * @return \Ramsey\Uuid\UuidInterface
     * @throws \Exception
     */
    public function uuid()
    {
        return Uuid::uuid4();
    }
}