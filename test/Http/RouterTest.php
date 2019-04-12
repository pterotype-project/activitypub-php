<?php

namespace ActivityPub\Test\Http;

use ActivityPub\Controllers\GetController;
use ActivityPub\Controllers\PostController;
use ActivityPub\Http\Router;
use ActivityPub\Test\TestConfig\APTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouterTest extends APTestCase
{
    /**
     * @var Router
     */
    private $router;

    private $getController;
    private $postController;
    private $kernel;

    public function setUp()
    {
        $this->getController = $this->getMock( GetController::class );
        $this->postController = $this->getMock( PostController::class );
        $this->router = new Router( $this->getController, $this->postController );
        $this->kernel = $this->getMock( HttpKernel::class );
    }

    public function provideTestRouter()
    {
        $this->getController = $this->getMock( GetController::class );
        $this->postController = $this->getMock( PostController::class );
        return array(
            array( array(
                'id' => 'GET',
                'request' => Request::create( 'https://foo.com', Request::METHOD_GET ),
                'expectedController' => array( $this->getController, 'handle' ),
            ) ),
            array( array(
                'id' => 'POST',
                'request' => Request::create( 'https://foo.com', Request::METHOD_POST ),
                'expectedController' => array( $this->postController, 'handle' ),
            ) ),
            array( array(
                'id' => 'MethodNotAllowed',
                'request' => Request::create( 'https://foo.com', Request::METHOD_PUT ),
                'expectedException' => MethodNotAllowedHttpException::class,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestRouter
     */
    public function testRouter( $testCase )
    {
        $request = $testCase['request'];
        $event = new GetResponseEvent(
            $this->kernel, $request, HttpKernelInterface::MASTER_REQUEST
        );
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        $this->router->route( $event );
        $this->assertEquals(
            $testCase['expectedController'],
            $request->attributes->get( '_controller' ),
            "Error on test $testCase[id]"
        );
    }
}

