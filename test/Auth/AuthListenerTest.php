<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Test\Auth;

use ActivityPub\Auth\AuthListener;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AuthListenerTest extends APTestCase
{
    private $objectsService;

    public function setUp()
    {
        $this->objectsService = $this->getMock( ObjectsService::class );
        $this->objectsService->method( 'dereference' )->will( $this->returnValueMap( array(
            array( 'https://example.com/actor/1', TestActivityPubObject::fromArray( array(
                'id' => 'https://example.com/actor/1',
            ) ) ),
            array( 'https://example.com/actor/2', TestActivityPubObject::fromArray( array(
                'id' => 'https://example.com/actor/2',
            ) ) ),
        ) ) );
    }

    public function provideTestAuthListener()
    {
        return array(
            array( array(
                'id' => 'basicTest',
                'authFunction' => function () {
                    return 'https://example.com/actor/1';
                },
                'expectedAttributes' => array(
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/1',
                    ) ),
                ),
            ) ),
            array( array(
                'id' => 'existingActorTest',
                'authFunction' => function () {
                    return 'https://example.com/actor/1';
                },
                'requestAttributes' => array(
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/2',
                    ) ),
                ),
                'expectedAttributes' => array(
                    'actor' => TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actor/2',
                    ) ),
                ),
            ) ),
            array( array(
                'id' => 'defaultAuthTest',
                'authFunction' => function () {
                    return false;
                },
                'expectedAttributes' => array(),
            ) ),
        );
    }

    /**
     * @dataProvider provideTestAuthListener
     */
    public function testAuthListener( $testCase )
    {
        $event = $this->getEvent();
        if ( array_key_exists( 'requestAttributes', $testCase ) ) {
            foreach ( $testCase['requestAttributes'] as $attribute => $value ) {
                $event->getRequest()->attributes->set( $attribute, $value );
            }
        }
        $authListener = new AuthListener(
            $testCase['authFunction'], $this->objectsService
        );
        $authListener->checkAuth( $event );
        foreach ( $testCase['expectedAttributes'] as $expectedKey => $expectedValue ) {
            $this->assertTrue(
                $event->getRequest()->attributes->has( $expectedKey ),
                "Error on test $testCase[id]"
            );
            if ( $expectedValue instanceof ActivityPubObject ) {
                $this->assertTrue(
                    $expectedValue->equals(
                        $event->getRequest()->attributes->get( $expectedKey )
                    ),
                    "Error on test $testCase[id]"
                );
            } else {
                $this->assertEquals(
                    $expectedValue,
                    $event->getRequest()->attributes->get( $expectedKey ),
                    "Error on test $testCase[id]"
                );
            }
        }
    }

    public function getEvent()
    {
        $kernel = $this->getMock( HttpKernelInterface::class );
        $request = Request::create( 'https://example.com/foo', Request::METHOD_GET );
        return new GetResponseEvent(
            $kernel, $request, HttpKernelInterface::MASTER_REQUEST
        );
    }
}

