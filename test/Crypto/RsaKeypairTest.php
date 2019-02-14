<?php
namespace ActivityPub\Test\Crypto;

use ActivityPub\Crypto\RsaKeypair;
use ActivityPub\Test\TestConfig\APTestCase;
use BadMethodCallException;

class RsaKeypairTest extends APTestCase
{
    public function testItCreatesKeypair()
    {
        $keypair = RsaKeypair::generate();
        $this->assertStringStartsWith( '-----BEGIN PUBLIC KEY-----', $keypair->getPublicKey() );
        $this->assertStringEndsWith( '-----END PUBLIC KEY-----', $keypair->getPublicKey() );
        $this->assertStringStartsWith(
            '-----BEGIN RSA PRIVATE KEY-----', $keypair->getPrivateKey()
        );
        $this->assertStringEndsWith(
            '-----END RSA PRIVATE KEY-----', $keypair->getPrivateKey()
        );
    }

    public function testItSignsAndValidatesSignatures()
    {
        $keypair = RsaKeypair::generate();
        $data = 'This is some data';
        $signature = $keypair->sign( $data );
        $this->assertInternalType( 'string', $signature );
        $this->assertNotEmpty( $signature );
        $verified = $keypair->verify( $data, $signature );
        $this->assertTrue( $verified );
    }

    public function testItGivesErrorValidatingInvalidSignature()
    {
        $keypair = RsaKeypair::generate();
        $data = 'This is some data';
        $signature = 'not a real signature';
        $this->setExpectedException( \PHPUnit_Framework_Error::class );
        $keypair->verify( $data, $signature );
    }

    public function testItReturnsNotVerifiedForValidButWrongSignature()
    {
        $keypairOne = RsaKeypair::generate();
        $data = 'This is some data';
        $signature = $keypairOne->sign( $data );
        $keypairTwo = RsaKeypair::generate();
        $verified = $keypairTwo->verify( $data, $signature );
        $this->assertFalse( $verified );
    }

    public function testItCreatesValidPublicKeyOnly()
    {
        $fullKeypair = RsaKeypair::generate();
        $publicKeyOnly = RsaKeypair::fromPublicKey( $fullKeypair->getPublicKey() );
        $data = 'This is some data';
        $signature = $fullKeypair->sign( $data );
        $verified = $publicKeyOnly->verify( $data, $signature );
        $this->assertTrue( $verified );
    }

    public function testItCannotSignWithPublicKeyOnly()
    {
        $fullKeypair = RsaKeypair::generate();
        $publicKeyOnly = RsaKeypair::fromPublicKey( $fullKeypair->getPublicKey() );
        $data = 'This is some data';
        $this->setExpectedException( BadMethodCallException::class, 'Unable to sign data without a private key' );
        $publicKeyOnly->sign( $data );
    }

    public function testItSignsAndVerifiesEmptyData()
    {
        $keypair = RsaKeypair::generate();
        $data = '';
        $signature = $keypair->sign( $data );
        $verified = $keypair->verify( $data, $signature );
        $this->assertTrue( $verified );
    }

    public function testItHandlesInvalidPublicKeyOnly()
    {
        $fullKeypair = RsaKeypair::generate();
        $publicKeyOnly = RsaKeypair::fromPublicKey( 'not a real public key' );
        $data = 'This is some data';
        $signature = $fullKeypair->sign( $data );
        $verified = $publicKeyOnly->verify( $data, $signature );
        $this->assertFalse( $verified );
    }
}

