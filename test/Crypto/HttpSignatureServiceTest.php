<?php
namespace ActivityPub\Test\Crypto;

use DateTime;
use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use GuzzleHttp\Psr7\Request as PsrRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class HttpSignatureServiceTest extends TestCase
{
    const PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDCFENGw33yGihy92pDjZQhl0C3
6rPJj+CvfSC8+q28hxA161QFNUd13wuCTUcq0Qd2qsBe/2hFyc2DCJJg0h1L78+6
Z4UMR7EOcpfdUE9Hf3m/hs+FUR45uBJeDK1HSFHD8bHKD6kv8FPGfJTotc+2xjJw
oYi+1hqp1fIekaxsyQIDAQAB
-----END PUBLIC KEY-----";

    const PRIVATE_KEY = "-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----";

    private $httpSignatureService;

    public function setUp()
    {
        $dateTimeProvider = new TestDateTimeProvider( array(
            'http-signature.verify' => DateTime::createFromFormat(
                DateTime::RFC2822, 'Sun, 05 Jan 2014 21:31:40 GMT'
            ),
        ) );
        $this->httpSignatureService = new HttpSignatureService( $dateTimeProvider );
    }

    private static function getSymfonyRequest()
    {
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
        return $request;
    }

    private static function getPsrRequest()
    {
        $headers = array(
            'Host' => 'example.com',
            'Content-Type' => 'application/json',
            'Digest' => 'SHA-256=X48E9qOokqqrvdts8nOJRJN3OWDUoyWxBf7kbu9DBPE=',
            'Content-Length' => 18,
            'Date' => 'Sun, 05 Jan 2014 21:31:40 GMT'
        );
        $body = '{"hello": "world"}';
        return new PsrRequest(
            'POST', 'https://example.com/foo?param=value&pet=dog', $headers, $body
        );
    }

    public function testItVerifies()
    {
        $testCases = array(
            array(
                'id' => 'defaultTest',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",signature="SjWJWbWN7i0wzBvtPl8rbASWz5xQW6mcJmn+ibttBqtifLN7Sazz6m79cNfwwb8DMJ5cou1s7uEGKKCs+FLEEaDV5lp7q25WqS+lavg7T8hc0GppauB6hbgEKTwblDHYGEtbGmtdHgVCk9SuS13F0hZ8FD0k/5OxEPXe5WozsbM="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'basicTest',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'allHeadersTest',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'defaultTestSigHeader',
                'headers' => array(
                    'Signature' => 'keyId="Test",algorithm="rsa-sha256",signature="SjWJWbWN7i0wzBvtPl8rbASWz5xQW6mcJmn+ibttBqtifLN7Sazz6m79cNfwwb8DMJ5cou1s7uEGKKCs+FLEEaDV5lp7q25WqS+lavg7T8hc0GppauB6hbgEKTwblDHYGEtbGmtdHgVCk9SuS13F0hZ8FD0k/5OxEPXe5WozsbM="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'basicTestSigHeader',
                'headers' => array(
                    'Signature' => 'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'allHeadersTestSigHeader',
                'headers' => array(
                    'Signature' => 'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'noHeaders',
                'headers' => array(),
                'expectedResult' => false,
            ),
            array(
                'id' => 'headerMissing',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length x-foo-header",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
                ), 
                'expectedResult' => false,
            ),
            array(
                'id' => 'malformedHeader',
                'headers' => array(
                    'Authorization' => 'not a real auth header',
                ),
                'expectedResult' => false,
            ),
            array(
                'id' => 'partlyMalformedHeader',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers-malformed="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
                ),
                'expectedResult' => false,
            ),
            array(
                'id' => 'dateTooFarInPast',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedResult' => false,
                'currentDatetime' => DateTime::createFromFormat(
                    DateTime::RFC2822, 'Sun, 05 Jan 2014 21:36:41 GMT'
                ),
            ),
            array(
                'id' => 'dateTooFarInFuture',
                'headers' => array(
                    'Authorization' => 'Signature keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date", signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
                ),
                'expectedResult' => false,
                'currentDatetime' => DateTime::createFromFormat(
                    DateTime::RFC2822, 'Sun, 05 Jan 2014 21:26:39 GMT'
                ),
            ),
        );
        foreach ( $testCases as $testCase ) {
            if ( array_key_exists( 'currentDatetime', $testCase ) ) {
                $dateTimeProvider = new TestDateTimeProvider( array(
                    'http-signature.verify' => $testCase['currentDatetime'],
                ) );
                $this->httpSignatureService = new HttpSignatureService( $dateTimeProvider );
            }
            $request = self::getSymfonyRequest();
            foreach ( $testCase['headers'] as $header => $value ) {
                $request->headers->set( $header, $value );
            }
            $actual = $this->httpSignatureService->verify( $request, self::PUBLIC_KEY );
            $this->assertEquals(
                $testCase['expectedResult'], $actual, "Error on test $testCase[id]"
            );
        }
    }

    public function testItSigns()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'keyId' => 'Test',
                'expected' => 'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date",signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
            ),
            array(
                'id' => 'allHeadersTest',
                'keyId' => 'Test',
                'headers' => array(
                    '(request-target)',
                    'host',
                    'date',
                    'content-type',
                    'digest',
                    'content-length',
                ),
                'expected' => 'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
            ),
        );
        foreach ( $testCases as $testCase ) {
            $request = self::getPsrRequest();
            if ( array_key_exists( 'headers', $testCase ) ) {
                $actual = $this->httpSignatureService->sign(
                    $request, self::PRIVATE_KEY, $testCase['keyId'], $testCase['headers']
                );
            } else {
                $actual= $this->httpSignatureService->sign(
                    $request, self::PRIVATE_KEY, $testCase['keyId']
                );
            }
            $this->assertEquals(
                $testCase['expected'], $actual, "Error on test $testCase[id]"
            );
        }
    }
}
?>
