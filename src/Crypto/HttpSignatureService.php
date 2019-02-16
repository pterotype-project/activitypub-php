<?php

namespace ActivityPub\Crypto;

use ActivityPub\Utils\DateTimeProvider;
use ActivityPub\Utils\HeaderUtils;
use ActivityPub\Utils\SimpleDateTimeProvider;
use DateTime;
use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * The HttpSignatureService provides methods to generate and verify HTTP signatures
 */
class HttpSignatureService
{
    // TODO handle the Digest header better, both on generating and verifying
    const REPLAY_THRESHOLD = 300;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * @var DiactorosFactory
     */
    private $psr7Factory;

    /**
     * Constructs a new HttpSignatureService
     *
     * @param DateTimeProvider $dateTimeProvider The DateTimeProvider,
     * defaults to SimpleDateTimeProvider
     */
    public function __construct( DateTimeProvider $dateTimeProvider = null )
    {
        if ( !$dateTimeProvider ) {
            $dateTimeProvider = new SimpleDateTimeProvider();
        }
        $this->dateTimeProvider = $dateTimeProvider;
        $this->psr7Factory = new DiactorosFactory();
    }

    /**
     * Generates a signature given the request and private key
     *
     * @param RequestInterface $request The request to be signed
     * @param string $privateKey The private key to use to sign the request
     * @param string $keyId The id of the signing key
     * @param array $headers |null The headers to use in the signature
     *                       (default ['(request-target)', 'host', 'date'])
     * @return string The Signature header value
     */
    public function sign( RequestInterface $request, $privateKey,
                          $keyId, $headers = null )
    {
        if ( !$headers ) {
            $headers = self::getDefaultHeaders();
        }
        $headers = array_map( 'strtolower', $headers );
        $signingString = $this->getSigningString( $request, $headers );
        $keypair = RsaKeypair::fromPrivateKey( $privateKey );
        $signature = base64_encode( $keypair->sign( $signingString, 'sha256' ) );
        $headersStr = implode( ' ', $headers );
        return "keyId=\"$keyId\"," .
            "algorithm=\"rsa-sha256\"," .
            "headers=\"$headersStr\"," .
            "signature=\"$signature\"";
    }

    public static function getDefaultHeaders()
    {
        return array(
            '(request-target)',
            'host',
            'date',
        );
    }

    /**
     * Returns the signing string from the request
     *
     * @param RequestInterface $request The request
     * @param array $headers The headers to use to generate the signing string
     * @return string The signing string
     */
    private function getSigningString( RequestInterface $request, $headers )
    {
        $signingComponents = array();
        foreach ( $headers as $header ) {
            $component = "${header}: ";
            if ( $header == '(request-target)' ) {
                $method = strtolower( $request->getMethod() );
                $path = $request->getUri()->getPath();
                $query = $request->getUri()->getQuery();
                if ( !empty( $query ) ) {
                    $path = "$path?$query";
                }
                $component = $component . $method . ' ' . $path;
            } else {
                // TODO handle 'digest' specially here too
                $values = $request->getHeader( $header );
                $component = $component . implode( ', ', $values );
            }
            $signingComponents[] = $component;
        }
        return implode( "\n", $signingComponents );
    }

    /**
     * Verifies the HTTP signature of $request
     *
     * @param Request $request The request to verify
     * @param string $publicKey The public key to use to verify the request
     * @return bool True if the signature is valid, false if it is missing or invalid
     */
    public function verify( Request $request, $publicKey )
    {
        $params = array();
        $headers = $request->headers;

        if ( !$headers->has( 'date' ) ) {
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
            substr( $headers->get( 'authorization' ), 0, 9 ) === 'Signature' ) {
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

        $psrRequest = $this->psr7Factory->createRequest( $request );
        $signingString = $this->getSigningString( $psrRequest, $targetHeaders );
        $signature = base64_decode( $params['signature'] );
        // TODO handle different algorithms here, checking the 'algorithm' param and the key headers
        $keypair = RsaKeypair::fromPublicKey( $publicKey );
        return $keypair->verify( $signingString, $signature, 'sha256' );
    }

    /**
     * Parses the signature params from the provided params string
     *
     * @param string $paramsStr The params represented as a string,
     * e.g. 'keyId="theKey",algorithm="rsa-sha256"'
     * @return array The params as an associative array
     */
    private function parseSignatureParams( $paramsStr )
    {
        $params = array();
        $split = HeaderUtils::split( $paramsStr, ',=' );
        foreach ( $split as $paramArr ) {
            $paramName = $paramArr[0];
            $paramValue = $paramArr[1];
            if ( $paramName == 'headers' ) {
                $paramValue = explode( ' ', $paramValue );
            }
            $params[$paramName] = $paramValue;
        }
        return $params;
    }
}

