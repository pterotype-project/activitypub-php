<?php
namespace ActivityPub;

use ActivityPub\Activities\CreateHandler;
use ActivityPub\Activities\NonActivityHandler;
use ActivityPub\Activities\UpdateHandler;
use ActivityPub\Activities\ValidationHandler;
use ActivityPub\Auth\AuthListener;
use ActivityPub\Auth\SignatureListener;
use ActivityPub\Config\ActivityPubConfig;
use ActivityPub\Config\ActivityPubModule;
use ActivityPub\Http\Router;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;

class ActivityPub
{
    /**
     * @var ActivityPubModule
     */
    private $module;

    /**
     * Constructs a new ActivityPub instance
     *
     * @param ActivityPubConfig $config Configuration options
     */
    public function __construct( ActivityPubConfig $config )
    {
        $this->module = new ActivityPubModule( $config);
    }

    /**
     * Handles an incoming ActivityPub request
     *
     * @param Request $request (optional) The Symfony request object.
     *   If not passed in, it is generated from the request globals.
     *
     * @return Response The response. Can be sent to the client with $response->send().
     */
    public function handle( $request = null )
    {
        if ( ! $request ) {
            $request = Request::createFromGlobals();
        }

        $dispatcher = $this->module->get( EventDispatcher::class );
        $dispatcher->addSubscriber( $this->module->get( Router::class ) );
        $dispatcher->addSubscriber( $this->module->get( AuthListener::class ) );
        $dispatcher->addSubscriber( $this->module->get( SignatureListener::class ) );
        $dispatcher->addSubscriber( new ExceptionListener() );

        $this->subscribeActivityHandlers( $dispatcher );

        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();

        $kernel = new HttpKernel(
            $dispatcher, $controllerResolver, new RequestStack(), $argumentResolver
        );
        return $kernel->handle( $request );
    }

    /**
     * Creates the database tables necessary for the library to function,
     * if they have not already been created.
     *
     * For best performance, this should only get called once in an application
     * (for example, when other database migrations get run).
     */
    public function updateSchema()
    {
        $entityManager = $this->module->get( EntityManager::class );
        $schemaTool = new SchemaTool( $entityManager );
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema( $classes );
    }

    /**
     * Sets up the activity handling pipeline
     *
     * @param EventDispatcher $dispatcher The dispatcher to attach the event
     * subscribers to
     */
    private function subscribeActivityHandlers( EventDispatcher $dispatcher )
    {
        $dispatcher->addSubscriber( $this->module->get( NonActivityHandler::class ) );
        $dispatcher->addSubscriber( $this->module->get( ValidationHandler::class ) );
        $dispatcher->addSubscriber( $this->module->get( CreateHandler::class ) );
        $dispatcher->addSubscriber( $this->module->get( UpdateHandler::class ) );
        $dispatcher->addSubscriber( $this->module->get( DeleteHandler::class ) );
    }
}
?>
