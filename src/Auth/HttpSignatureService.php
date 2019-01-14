<?php
namespace ActivityPub\Auth;

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
    
    public function sign( Request $request, string $privateKey, $headers = self::DEFAULT_HEADERS )
    {
        // To generate a signature for a request:
        // 1. put together the signing string from the headers list
        // 2. generate an RSA-sha256 signature of the signing string using the private key
        // 3. return the signature base64-encoded
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
        // To verify a signature:
        // 1. Re-create the signing string from the request and the headers
        // 2. verify that the signature is signed correctly using the public key and the signing string
        // The signature can either be in the Authentication header or the Signature header.
        // If it's in the Authentication header, the params will be prefixed with the string "Signature",
        // e.g. Authentication: Signature keyId="key-1",algorithm="rsa-sha256",headers="(request-target) host date",signature="thesig"
        // as opposed to the Signature header, which just has the params as its value:
        // Signature: keyId="key-1",algorithm="rsa-sha256",headers="(request-target) host date",signature="thesig"
        $params = array();
        $headers = $request->headers;
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
        $signature = $params['signature'];
        // TODO handle different algorithms here, checking the 'algorithm' param and the key headers
        return openssl_verify(
            $signingString, $signature, $publicKey, OPENSSL_ALGO_SHA256
        ) === 1;
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
                $method = strtolower( $request->method );
                $path = $request->getRequestUri();
                $component = $component . $method . ' ' . $path;
            } else {
                // TODO handle 'digest' specially here too
                $values = $request->headers->get( $header, null, false );
                $component = $component . implode( ', ', $values );
            }
            $signingComponents[] = $component;
        }
        return implode( '\n', $signingComponents );
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
        $split = HeaderUtils::split( $paramsStr, ',= ' );
        foreach ( $split as $paramArr ) {
            $paramName = $paramArr[0];
            $paramValue = $paramArr[1];
            if ( count( $paramValue ) === 1 ) {
                $paramValue = $paramValue[0];
            }
            $params[$paramName] = $paramValue;
        }
        return $params;
    }
}
?>
