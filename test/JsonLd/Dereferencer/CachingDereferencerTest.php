<?php

namespace ActivityPub\Test\JsonLd\Dereferencer;

use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\JsonLd\Dereferencer\CachingDereferencer;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use ActivityPub\Utils\Logger;
use Cache\Adapter\PHPArray\ArrayCachePool;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class CachingDereferencerTest extends APTestCase
{
    public function provideForItCaches()
    {
        return array(
            array(
                'https://example.com/sally',
                (object) array(
                    'id' => 'https://example.com/sally',
                    'type' => 'Person',
                ),
                array(
                    new Request(
                        'GET',
                        'https://example.com/sally',
                        array(
                            'Host' => 'example.com',
                            'Accept' => 'application/ld+json',
                            'Date' => 'Sun, 05 Jan 2014 21:31:40 GMT',
                        )
                    ),
                ),
                array(
                    new Response( 200, array(), json_encode(
                        (object) array(
                            'id' => 'https://example.com/sally',
                            'type' => 'Person',
                        )
                    )),
                ),
            ),
            array(
                'https://example.com/sally',
                (object) array(
                    'id' => 'https://example.com/sally',
                    'type' => 'Person',
                ),
                array(
                    new Request(
                        'GET',
                        'https://example.com/sally',
                        array(
                            'Host' => 'example.com',
                            'Accept' => 'application/ld+json',
                            'Date' => 'Sun, 05 Jan 2014 21:31:40 GMT',
                            'Signature' => 'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date",signature="PXfYgMWE1CpS7DDuo8iB7Sj3qCBuN8bDuND3mQBU06rh7EMfWqisB0USH0DWFbZVcbsHr/YnKJlcmbWG5mpU6Kf0B4SAoMKGHCUNT1i55uw5XLPSZfrd2c38md2Pv8Dt0lO7cFP8SeiTlBM3gTmpvnuKn+AxpR9jpvwAQT8riQw="',
                        )
                    ),
                ),
                array(
                    new Response( 200, array(), json_encode(
                        (object) array(
                            'id' => 'https://example.com/sally',
                            'type' => 'Person',
                        )
                    )),
                ),
                'Test',
                "-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQDCFENGw33yGihy92pDjZQhl0C36rPJj+CvfSC8+q28hxA161QF
NUd13wuCTUcq0Qd2qsBe/2hFyc2DCJJg0h1L78+6Z4UMR7EOcpfdUE9Hf3m/hs+F
UR45uBJeDK1HSFHD8bHKD6kv8FPGfJTotc+2xjJwoYi+1hqp1fIekaxsyQIDAQAB
AoGBAJR8ZkCUvx5kzv+utdl7T5MnordT1TvoXXJGXK7ZZ+UuvMNUCdN2QPc4sBiA
QWvLw1cSKt5DsKZ8UETpYPy8pPYnnDEz2dDYiaew9+xEpubyeW2oH4Zx71wqBtOK
kqwrXa/pzdpiucRRjk6vE6YY7EBBs/g7uanVpGibOVAEsqH1AkEA7DkjVH28WDUg
f1nqvfn2Kj6CT7nIcE3jGJsZZ7zlZmBmHFDONMLUrXR/Zm3pR5m0tCmBqa5RK95u
412jt1dPIwJBANJT3v8pnkth48bQo/fKel6uEYyboRtA5/uHuHkZ6FQF7OUkGogc
mSJluOdc5t6hI1VsLn0QZEjQZMEOWr+wKSMCQQCC4kXJEsHAve77oP6HtG/IiEn7
kpyUXRNvFsDE0czpJJBvL/aRFUJxuRK91jhjC68sA7NsKMGg5OXb5I5Jj36xAkEA
gIT7aFOYBFwGgQAQkWNKLvySgKbAZRTeLBacpHMuQdl1DfdntvAyqpAZ0lY0RKmW
G6aFKaqQfOXKCyWoUiVknQJAXrlgySFci/2ueKlIE1QqIiLSZ8V8OlpFLRnb1pzI
7U1yQXnTAEFYM560yJlzUpOb1V4cScGd365tiSMvxLOvTA==
-----END RSA PRIVATE KEY-----",
            ),
        );
    }

    /**
     * @dataProvider provideForItCaches
     */
    public function testItCaches( $iri,
                                  $expectedJsonObj,
                                  $expectedRequestHistory = array(),
                                  $mockResponses = array(),
                                  $keyId = null,
                                  $privateKey = null )
    {
        $logger = new Logger();
        $cache = new ArrayCachePool();
        $dateTimeProvider = new TestDateTimeProvider( array (
            'caching-dereferencer.dereference' => DateTime::createFromFormat(
                DateTime::RFC2822, 'Sun, 05 Jan 2014 21:31:40 GMT'
            ),
        ) );
        $sigService = new HttpSignatureService( $dateTimeProvider );

        $mock = new MockHandler( $mockResponses );
        $handler = HandlerStack::create( $mock );
        $historyContainer = array();
        $history = Middleware::history( $historyContainer );
        $handler->push( $history );
        $client = new Client( array( 'handler' => $handler ) );

        $dereferencer = new CachingDereferencer(
            $logger, $cache, $client, $sigService, $dateTimeProvider, $keyId, $privateKey
        );

        $jsonObj = $dereferencer->dereference( $iri );
        $this->assertEquals( $expectedJsonObj, $jsonObj );

        $cachedObj = $dereferencer->dereference( $iri );
        $this->assertEquals( $expectedJsonObj, $cachedObj );

        $this->assertEquals( count( $expectedRequestHistory ), count( $historyContainer ) );
        for ( $i = 0; $i < count( $expectedRequestHistory ); $i += 1 ) {
            $expectedRequest = $expectedRequestHistory[$i];
            $actualRequest = $historyContainer[$i]['request'];
            foreach ( $expectedRequest->getHeaders() as $name => $expectedHeaders ) {
                $this->assertEquals( $expectedHeaders, $actualRequest->getHeader( $name ) );
            }
        }
    }
}