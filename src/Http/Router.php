<?php
namespace ActivityPub\Http;

use ActivityPub\Controllers\GetController;
use ActivityPub\Controllers\PostController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class Router implements EventSubscriberInterface
{
    /**
     * @var GetController
     */
    private $getController;

    /**
     * @var PostController
     */
    private $postController;

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'route',
        );
    }

    public function __construct( GetController $getController,
                                 PostController $postController )
    {
        $this->getController = $getController;
        $this->postController = $postController;
    }
    /**
     * Routes the request by setting the _controller attribute
     *
     * @param GetResponseEvent $event The request event
     */
    public function route( GetResponseEvent $event )
    {
        $request = $event->getRequest();
        if ( $request->getMethod() === Request::METHOD_GET ) {
            $request->attributes->set(
                '_controller', array( $this->getController, 'handle' )
            );
        } else if ( $request->getMethod() === Request::METHOD_POST ) {
            $request->attributes->set(
                '_controller', array( $this->postController, 'handle' )
            );
        } else {
            throw new MethodNotAllowedHttpException( array(
                Request::METHOD_GET, Request::METHOD_POST
            ) );
        }
    }
}

