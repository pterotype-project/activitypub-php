<?php

namespace ActivityPub\Test\Auth;

use ActivityPub\Auth\AuthService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\HttpFoundation\Request;

class AuthServiceTest extends APTestCase
{
    /**
     * @var AuthService
     */
    private $authService;

    public function setUp()
    {
        $this->authService = new AuthService();
    }

    public function provideTestAuthService()
    {
        return array(
            array( array(
                'id' => 'addressedTo',
                'actor' => 'https://example.com/actor/1',
                'object' => array(
                    'to' => 'https://example.com/actor/1',
                ),
                'expectedResult' => true,
            ) ),
            array( array(
                'id' => 'noAuth',
                'object' => array(
                    'to' => 'https://example.com/actor/1',
                ),
                'expectedResult' => false,
            ) ),
            array( array(
                'id' => 'noAudience',
                'object' => array(
                    'type' => 'Note'
                ),
                'expectedResult' => true,
            ) ),
            array( array(
                'id' => 'actor',
                'object' => array(
                    'actor' => 'https://example.com/actor/1',
                    'to' => 'https://example.com/actor/2',
                ),
                'actor' => 'https://example.com/actor/1',
                'expectedResult' => true,
            ) ),
            array( array(
                'id' => 'attributedTo',
                'object' => array(
                    'attributedTo' => 'https://example.com/actor/1',
                    'to' => 'https://example.com/actor/2',
                ),
                'actor' => 'https://example.com/actor/1',
                'expectedResult' => true,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestAuthService
     */
    public function testAuthService( $testCase )
    {
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

