<?php
namespace ActivityPub\Test;

use ActivityPub\Crypto\RsaKeypair;
use PHPUnit\Framework\TestCase;

class RsaKeypairTest extends TestCase
{
    public function testItCreatesKeypair()
    {
        $keypair = RsaKeypair::generate();
        $this->assertStringStartsWith( '-----BEGIN PUBLIC KEY-----', $keypair->getPublicKey() );
        $this->assertStringEndsWith( '-----END PUBLIC KEY-----', $keypair->getPublicKey() );
    }
}
?>
