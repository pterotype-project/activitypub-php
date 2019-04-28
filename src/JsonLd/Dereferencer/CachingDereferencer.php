<?php

namespace ActivityPub\JsonLd\Dereferencer;

use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\JsonLd\Exceptions\NodeNotFoundException;
use ActivityPub\Utils\DateTimeProvider;
use ActivityPub\Utils\Util;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * A dereferencer that caches its results by id. Signs outgoing requests if provided with a keyId and a privateKey.
 * Class CachingDereferencer
 * @package ActivityPub\JsonLd\Dereferencer
 */
class CachingDereferencer implements DereferencerInterface
{
    const DEFAULT_CACHE_TTL = 3600;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The cache.
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * The HTTP client.
     * @var Client
     */
    private $httpClient;

    /**
     * @var HttpSignatureService
     */
    private $httpSignatureService;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * The keyId for signing requests, if there is one.
     * @var null|string
     */
    private $keyId;

    /**
     * The private key for signing requests, if there is one.
     * @var null|string
     */
    private $privateKey;

    /**
     * CachingDereferencer constructor.
     * @param LoggerInterface $logger
     * @param CacheItemPoolInterface $cache
     * @param Client $httpClient
     * @param HttpSignatureService $httpSignatureService
     * @param DateTimeProvider $dateTimeProvider
     * @param null|string $keyId
     * @param null|string $privateKey
     */
    public function __construct( LoggerInterface $logger,
                                 CacheItemPoolInterface $cache,
                                 Client $httpClient,
                                 HttpSignatureService $httpSignatureService,
                                 DateTimeProvider $dateTimeProvider,
                                 $keyId = null,
                                 $privateKey = null )
    {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->httpClient = $httpClient;
        $this->httpSignatureService = $httpSignatureService;
        $this->dateTimeProvider = $dateTimeProvider;
        $this->keyId = $keyId;
        $this->privateKey = $privateKey;
    }

    public function setKeyId( $keyId )
    {
        $this->keyId = $keyId;
    }

    public function setPrivateKey( $privateKey )
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param string $iri The IRI to dereference.
     * @return stdClass|array The dereferenced node.
     * @throws NodeNotFoundException If a node with the IRI could not be found.
     */
    public function dereference( $iri )
    {
        $key = $this->makeCacheKey( $iri );
        $cacheItem = $this->cache->getItem( $key );
        if ( $cacheItem->isHit() ) {
            return $cacheItem->get();
        } else {
            if ( Util::isLocalUri( $iri ) ) {
                // TODO fetch object from persistence backend
            }
            $headers = array(
                'Accept' => 'application/ld+json',
                'Date' => $this->getNowRFC1123(),
            );
            $request = new Request( 'GET', $iri, $headers );
            if ( $this->shouldSign() ) {
                $signature = $this->httpSignatureService->sign( $request, $this->privateKey, $this->keyId );
                $request = $request->withHeader( 'Signature', $signature );
            }
            $response = $this->httpClient->send( $request, array( 'http_errors' => false ) );
            if ( $response->getStatusCode() >= 400 ) {
                $statusCode = $response->getStatusCode();
                $this->logger->error(
                    "[ActivityPub-PHP] Received response with status $statusCode from $iri",
                    array( 'request' => $request, 'response' => $response )
                );
            } else {
                $body = json_decode( $response->getBody() );
                if ( ! $body ) {
                    throw new NodeNotFoundException( $iri );
                }
                $cacheItem->set( $body );
                $cacheItem->expiresAfter( self::DEFAULT_CACHE_TTL );
                $this->cache->save( $cacheItem );
                return $body;
            }
        }
    }

    /**
     * Generates a valid cache key for $id.
     * @param string $id
     * @return string
     */
    private function makeCacheKey( $id )
    {
        return str_replace( array( '{', '}', '(', ')', '/', '\\', '@', ':' ), '_', $id );
    }

    /**
     * True if the dereferencer should sign outgoing requests.
     * @return bool
     */
    private function shouldSign()
    {
        return $this->keyId && $this->privateKey;
    }

    private function getNowRFC1123()
    {
        $now = $this->dateTimeProvider->getTime( 'caching-dereferencer.dereference' );
        $now->setTimezone( new DateTimeZone( 'GMT' ) );
        return $now->format( 'D, d M Y H:i:s T' );
    }
}