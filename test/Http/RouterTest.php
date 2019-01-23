<?php
namespace ActivityPub\Test\Http;

use ActivityPub\Controllers\GetController;
use ActivityPub\Controllers\PostController;
use ActivityPub\Http\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class RouterTest extends TestCase
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
        $this->getController = $this->createMock( GetController::class );
        $this->postController = $this->createMock( PostController::class );
        $this->router = new Router( $this->getController, $this->postController );
        $this->kernel = $this->createMock( Kernel::class );
    }
    
    public function testRouter()
    {
        $testCases = array(
            array(
                'id' => 'GET',
                'request' => Request::create( 'https://foo.com', Request::METHOD_GET ),
                'expectedController' => array( $this->getController, 'handle' ),
            ),
            array(
                'id' => 'POST',
                'request' => Request::create( 'https://foo.com', Request::METHOD_POST ),
                'expectedController' => array( $this->postController, 'handle' ),
            ),
            array(
                'id' => 'MethodNotAllowed',
                'request' => Request::create( 'https://foo.com', Request::METHOD_PUT ),
                'expectedException' => MethodNotAllowedHttpException::class,
            ),
        );
        foreach( $testCases as $testCase ) {
            $request = $testCase['request'];
            $event = new GetResponseEvent(
                $this->kernel, $request, HttpKernelInterface::MASTER_REQUEST
            );
            if ( array_key_exists( 'expectedException', $testCase ) ) {
                $this->expectException( $testCase['expectedException'] );
            }
            $this->router->route( $event );
            $this->assertEquals(
                $testCase['expectedController'],
                $request->attributes->get( '_controller' ),
                "Error on test $testCase[id]"
            );
        }
    }
}
?>
