<?php
namespace ActivityPub\Test\Auth;

use ActivityPub\Auth\AuthService;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Test\TestConfig\APTestCase;
use Symfony\Component\HttpFoundation\Request;

class AuthServiceTest extends APTestCase
{
    private $authService;

    public function setUp()
    {
        $this->authService = new AuthService();
    }

    public function testAuthService()
    {
        $testCases = array(
            array(
                'id' => 'addressedTo',
                'actor' => 'https://example.com/actor/1',
                'object' => array(
                    'to' => 'https://example.com/actor/1',
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'noAuth',
                'object' => array(
                    'to' => 'https://example.com/actor/1',
                ),
                'expectedResult' => false,
            ),
            array(
                'id' => 'noAudience',
                'object' => array(
                    'type' => 'Note'
                ),
                'expectedResult' => true,
            ),
            array(
                'id' => 'actor',
                'object' => array(
                    'actor' => 'https://example.com/actor/1',
                    'to' => 'https://example.com/actor/2',
                ),
                'actor' => 'https://example.com/actor/1',
                'expectedResult' => true,
            ),
            array(
                'id' => 'attributedTo',
                'object' => array(
                    'attributedTo' => 'https://example.com/actor/1',
                    'to' => 'https://example.com/actor/2',
                ),
                'actor' => 'https://example.com/actor/1',
                'expectedResult' => true,
            ),
        );
        foreach ( $testCases as $testCase ) {
            $request = Request::create( 'https://example.com/objects/1' );
            if ( array_key_exists( 'actor', $testCase ) ) {
                $request->attributes->set( 'actor', $testCase['actor'] );
            }
            $object = TestActivityPubObject::fromArray( $testCase['object'] );
            $actual = $this->authService->isAuthorized( $request, $object );
            $this->assertEquals(
                $testCase['expectedResult'], $actual, "Error on test $testCase[id]"
            );
        }
    }
}

