<?php

namespace ActivityPub\Test\Auth;

use ActivityPub\Auth\SignatureListener;
use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SignatureListenerTest extends APTestCase
{
    const ACTOR_ID = 'https://example.com/actor/1';
    const KEY_ID = 'https://example.com/actor/1/key';
    const PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDCFENGw33yGihy92pDjZQhl0C3
6rPJj+CvfSC8+q28hxA161QFNUd13wuCTUcq0Qd2qsBe/2hFyc2DCJJg0h1L78+6
Z4UMR7EOcpfdUE9Hf3m/hs+FUR45uBJeDK1HSFHD8bHKD6kv8FPGfJTotc+2xjJw
oYi+1hqp1fIekaxsyQIDAQAB
-----END PUBLIC KEY-----";

    /**
     * @var SignatureListener
     */
    private $signatureListener;

    public function setUp()
    {
        $dateTimeProvider = new TestDateTimeProvider( array(
            'http-signature.verify' => DateTime::createFromFormat(
                DateTime::RFC2822, 'Sun, 05 Jan 2014 21:31:40 GMT'
            ),
        ) );
        $httpSignatureService = new HttpSignatureService( $dateTimeProvider );
        $objectsService = $this->getMock( ObjectsService::class );
        $objectsService->method( 'dereference' )
            ->will( $this->returnValueMap( array(
                array( self::KEY_ID, TestActivityPubObject::fromArray( self::getKey() ) ),
                array( self::ACTOR_ID, TestActivityPubObject::fromArray( self::getActor() ) ),
            ) ) );
        $this->signatureListener = new SignatureListener(
            $httpSignatureService, $objectsService
        );
    }

    private static function getKey()
    {
        return array(
            'id' => self::KEY_ID,
            'owner' => 'https://example.com/actor/1',
            'publicKeyPem' => self::PUBLIC_KEY,
        );
    }

    private static function getActor()
    {
        return array( 'id' => self::ACTOR_ID );
    }

    public function provideTestSignatureListener()
    {
        return array(
            array( array(
                'id' => 'basicTest',
                'headers' => array(
                    'Authorization' => 'Signature keyId="https://example.com/actor/1/key",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedAttributes' => array(
                    'signed' => true,
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                ),
            ) ),
            array( array(
                'id' => 'existingActorTest',
                'headers' => array(
                    'Authorization' => 'Signature keyId="https://example.com/actor/1/key",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'requestAttributes' => array(
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/2',
                    ) ),
                ),
                'expectedAttributes' => array(
                    'signed' => true,
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/2',
                    ) ),
                ),
            ) ),
            array( array(
                'id' => 'signatureHeaderTest',
                'headers' => array(
                    'Signature' => 'keyId="https://example.com/actor/1/key",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedAttributes' => array(
                    'signed' => true,
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                ),
            ) ),
            array( array(
                'id' => 'noSignatureTest',
                'expectedAttributes' => array(),
            ) ),
        );
    }

    /**
     * @dataProvider provideTestSignatureListener
     */
    public function testSignatureListener( $testCase )
    {
        $event = $this->getEvent();
        if ( array_key_exists( 'headers', $testCase ) ) {
            foreach ( $testCase['headers'] as $header => $value ) {
                $event->getRequest()->headers->set( $header, $value );
            }
        }
        if ( array_key_exists( 'requestAttributes', $testCase ) ) {
            foreach ( $testCase['requestAttributes'] as $attribute => $value ) {
                $event->getRequest()->attributes->set( $attribute, $value );
            }
        }
        $this->signatureListener->validateHttpSignature( $event );
        $this->assertEquals(
            $testCase['expectedAttributes'],
            $event->getRequest()->attributes->all(),
            "Error on test $testCase[id]"
        );
    }

    private function getEvent()
    {
        $kernel = $this->getMock( HttpKernelInterface::class );
        $request = Request::create(
            'https://example.com/foo?param=value&pet=dog',
            Request::METHOD_POST,
            array(),
            array(),
            array(),
            array(),
            '{"hello": "world"}'
        );
        $request->headers->set( 'host', 'example.com' );
        $request->headers->set( 'content-type', 'application/json' );
        $request->headers->set(
            'digest', 'SHA-256=X48E9qOokqqrvdts8nOJRJN3OWDUoyWxBf7kbu9DBPE='
        );
        $request->headers->set( 'content-length', 18 );
        $request->headers->set( 'date', 'Sun, 05 Jan 2014 21:31:40 GMT' );
        $event = new GetResponseEvent( $kernel, $request, HttpKernelInterface::MASTER_REQUEST );
        return $event;
    }
}

