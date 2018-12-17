<?php
namespace ActivityPub\Crypto;

use \phpseclib\Crypt\Rsa;

class RsaKeypair
{
    /**
     * The RSA instance associated with this keypair
     *
     * @var RSA
     */
    private $rsa;
    
    private function __construct( RSA $rsa )
    {
        $rsa->setHash( 'sha256' );
        $this->rsa = $rsa;
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
     * Generates a signature for $data
     *
     * Throws a BadMethodCallException if this RsaKeypair does not have a private key.
     * @param string $data The data to sign
     * @return string The signature
     */
    public function sign( $data )
    {
        if ( ! $this->$rsa->getPrivateKey() ) {
            throw new BadMethodCallException(
                'Unable to sign data without a private key'
            );
        }
        return $this->$rsa->sign( $data );
    }

    /**
     * Verifies $signature for $data using this keypair's public key
     *
     * @param string $data The data
     * @param string $signature The signature
     * @return boolean
     */
    public function verify( $data, $signature )
    {
        return $this->$rsa->verify( $data, $signature );
    }

    /**
     * Generates a new keypair
     *
     * @return RsaKeypair
     */
    public static function generate()
    {
        $rsa = new RSA();
        $rsa->createKey( 2048 );
        return new RsaKeypair( $rsa );
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
    public function fromPublicKey( string $publicKey )
    {
        $rsa = new RSA();
        $rsa->loadKey( $publicKey );
        return new RsaKeypair( $rsa );
    }
}
?>
