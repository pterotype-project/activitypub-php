<?php
namespace ActivityPub\Test\Auth;

use ActivityPub\Auth\AuthListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use PHPUnit\Framework\TestCase;

class AuthListenerTest extends TestCase
{

    public function getEvent()
    {
        $kernel = $this->createMock( HttpKernelInterface::class );
        $request = Request::create( 'https://example.com/foo', Request::METHOD_GET );
        return new GetResponseEvent(
            $kernel, $request, HttpKernelInterface::MASTER_REQUEST
        );
    }
    
    public function testAuthListener()
    {
        $testCases = array(
            array(
                'id' => 'basicTest',
                'authFunction' => function() {
                    return 'https://example.com/actor/1';
                },
                'expectedAttributes' => array(
                    'actor' => 'https://example.com/actor/1',
                ),
            ),
            array(
                'id' => 'existingActorTest',
                'authFunction' => function() {
                    return 'https://example.com/actor/1';
                },
                'requestAttributes' => array(
                    'actor' => 'https://example.com/actor/2',
                ),
                'expectedAttributes' => array(
                    'actor' => 'https://example.com/actor/2',
                ),
            ),
            array(
                'id' => 'defaultAuthTest',
                'authFunction' => function() {
                    return false;
                },
                'expectedAttributes' => array(),
            ),
        );
        foreach ( $testCases as $testCase ) {
            $event = $this->getEvent();
            if ( array_key_exists( 'requestAttributes', $testCase ) ) {
                foreach ( $testCase['requestAttributes'] as $attribute => $value ) {
                    $event->getRequest()->attributes->set( $attribute, $value );
                }
            }
            $authListener = new AuthListener( $testCase['authFunction'] );
            $authListener->checkAuth( $event );
            $this->assertEquals(
                $testCase['expectedAttributes'],
                $event->getRequest()->attributes->all(),
                "Error on test $testCase[id]"
            );
        }
    }
}
?>
