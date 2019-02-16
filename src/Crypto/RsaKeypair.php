<?php

namespace ActivityPub\Crypto;

use BadMethodCallException;
use phpseclib\Crypt\RSA;

class RsaKeypair
{
    /**
     * The public key
     *
     * @var string
     *
     */
    private $publicKey;

    /**
     * The private key
     *
     * @var string|null
     *
     */
    private $privateKey;

    public function __construct( $publicKey, $privateKey )
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * Generates a new keypair
     *
     * @return RsaKeypair
     */
    public static function generate()
    {
        $rsa = new RSA();
        $key = $rsa->createKey( 2048 );
        return new RsaKeypair( $key['publickey'], $key['privatekey'] );
    }

    /**
     * Generates an RsaKeypair with the given public key
     *
     * The generated RsaKeypair will be able to verify signatures but
     *   not sign data, since it won't have a private key.
     *
     * @param string $publicKey The public key
     * @return RsaKeypair
     */
    public static function fromPublicKey( $publicKey )
    {
        return new RsaKeypair( $publicKey, '' );
    }

    /**
     * Generates an RsaKeypair with the given private key
     *
     * The generated RsaKeypair will be able to sign data but
     *   not verify signatures, since it won't have a public key.
     *
     * @param string $privateKey The private key
     * @return RsaKeypair
     */
    public static function fromPrivateKey( $privateKey )
    {
        return new RsaKeypair( '', $privateKey );
    }

    /**
     * Returns the public key as a string
     *
     * @return string The public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Returns the private key as a string
     *
     * @return string The private key
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * Generates a signature for $data
     *
     * Throws a BadMethodCallException if this RsaKeypair does not have a private key.
     * @param string $data The data to sign
     * @param string $hash The hash algorithm to use. One of:
     * 'md2', 'md5', 'sha1', 'sha256', 'sha384', 'sha512'. Default: 'sha256'
     * @return string The signature
     */
    public function sign( $data, $hash = 'sha256' )
    {
        if ( empty( $this->privateKey ) ) {
            throw new BadMethodCallException(
                'Unable to sign data without a private key'
            );
        }
        $rsa = new RSA();
        $rsa->setHash( $hash );
        $rsa->setSignatureMode( RSA::SIGNATURE_PKCS1 );
        $rsa->loadKey( $this->privateKey );
        return $rsa->sign( $data );
    }

    /**
     * Verifies $signature for $data using this keypair's public key
     *
     * @param string $data The data
     * @param string $signature The signature
     * @param string $hash The hash algorithm to use. One of:
     * 'md2', 'md5', 'sha1', 'sha256', 'sha384', 'sha512'. Default: 'sha256'
     * @return bool
     */
    public function verify( $data, $signature, $hash = 'sha256' )
    {
        // TODO this throws a "Signature representative out of range" occasionally
        // I have no idea what that means or how to fix it
        $rsa = new RSA();
        $rsa->setHash( $hash );
        $rsa->setSignatureMode( RSA::SIGNATURE_PKCS1 );
        $rsa->loadKey( $this->publicKey );
        return $rsa->verify( $data, $signature );
    }
}

