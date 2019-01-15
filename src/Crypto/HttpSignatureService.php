<?php
namespace ActivityPub\Crypto;

use DateTime;
use ActivityPub\Utils\DateTimeProvider;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * The HttpSignatureService provides methods to generate and verify HTTP signatures
 */
class HttpSignatureService
{
    // TODO handle the Digest header better, both on generating and verifying
    const DEFAULT_HEADERS = array(
        '(request-target)',
        'host',
        'date',
    );

    const REPLAY_THRESHOLD = 300;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * Constructs a new HttpSignatureService
     *
     * @param DateTimeProvider $dateTimeProvider The DateTimeProvider, 
     * defaults to SimpleDateTimeProvider
     */
    public function __construct( DateTimeProvider $dateTimeProvider = null )
    {
        if ( ! $dateTimeProvider ) {
            $dateTimeProvider = new SimpleDateTimeProvider();
        }
        $this->dateTimeProvider = $dateTimeProvider;
    }
    
    /**
     * Generates a signature given the request and private key
     *
     * @param Request $request The request to be signed
     * @param string $privateKey The private key to use to sign the request
     * @param string $keyId The id of the signing key
     * @param array $headers The headers to use in the signature 
     *                       (default ['(request-target)', 'host', 'date'])
     * @return string The Signature header value
     */
    public function sign( Request $request, string $privateKey, string $keyId,
                          $headers = self::DEFAULT_HEADERS )
    {
        $headers = array_map( 'strtolower', $headers );
        $signingString = $this->getSigningString( $request, $headers );
        $keypair = RsaKeypair::fromPrivateKey( $privateKey );
        $signature = base64_encode( $keypair->sign( $signingString, 'rsa256' ) );
        $headersStr = implode( ' ', $headers );
        return "keyId=\"$keyId\"," .
            "algorithm=\"rsa-sha256\"," .
            "headers=\"$headersStr\"," .
            "signature=\"$signature\"";
    }

    /**
     * Verifies the HTTP signature of $request
     *
     * @param Request $request The request to verify
     * @param string $publicKey The public key to use to verify the request
     * @return bool True if the signature is valid, false if it is missing or invalid
     */
    public function verify( Request $request, string $publicKey )
    {
        $params = array();
        $headers = $request->headers;

        if ( ! $headers->has( 'date' ) ) {
            return false;
        }
        $now = $this->dateTimeProvider->getTime( 'http-signature.verify' );
        $then = DateTime::createFromFormat( DateTime::RFC2822, $headers->get( 'date' ) );
        if ( abs( $now->getTimestamp() - $then->getTimestamp() ) > self::REPLAY_THRESHOLD ) {
            return false;
        }

        if ( $headers->has( 'signature' ) ) {
            $params = $this->parseSignatureParams( $headers->get( 'signature' ) );
        } else if ( $headers->has( 'authorization' ) &&
                    substr($headers->get( 'authorization' ), 0, 9) === 'Signature' ) {
            $paramsStr = substr( $headers->get( 'authorization' ), 10 );
            $params = $this->parseSignatureParams( $paramsStr );
        }

        if ( count( $params ) === 0 ) {
            return false;
        }

        $targetHeaders = array( 'date' );
        if ( array_key_exists( 'headers', $params ) ) {
            $targetHeaders = $params['headers'];
        }

        $signingString = $this->getSigningString( $request, $targetHeaders );
        $signature = base64_decode( $params['signature'] );
        // TODO handle different algorithms here, checking the 'algorithm' param and the key headers
        $keypair = RsaKeypair::fromPublicKey( $publicKey );
        return $keypair->verify($signingString, $signature, 'sha256');
    }

    /**
     * Returns the signing string from the request
     *
     * @param Request $request The request
     * @param array $headers The headers to use to generate the signing string
     * @return string The signing string
     */
    private function getSigningString( Request $request, $headers )
    {
        $signingComponents = array();
        foreach ( $headers as $header ) {
            $component = "${header}: ";
            if ( $header == '(request-target)' ) {
                $method = strtolower( $request->getMethod());
                $path = $request->getRequestUri();
                $component = $component . $method . ' ' . $path;
            } else {
                // TODO handle 'digest' specially here too
                $values = $request->headers->get( $header, null, false );
                $component = $component . implode( ', ', $values );
            }
            $signingComponents[] = $component;
        }
        return implode( "\n", $signingComponents );
    }

    /**
     * Parses the signature params from the provided params string
     *
     * @param string $paramsStr The params represented as a string, 
     * e.g. 'keyId="theKey",algorithm="rsa-sha256"'
     * @return array The params as an associative array
     */
    private function parseSignatureParams( string $paramsStr )
    {
        $params = array();
        $split = HeaderUtils::split( $paramsStr, ',=' );
        foreach ( $split as $paramArr ) {
            $paramName = $paramArr[0];
            $paramValue = $paramArr[1];
            if ( $paramName == 'headers' ) {
                $paramValue = explode(' ', $paramValue);
            }
            $params[$paramName] = $paramValue;
        }
        return $params;
    }
}
?>
