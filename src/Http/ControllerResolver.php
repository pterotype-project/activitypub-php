<?php
namespace ActivityPub\Http;

use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Controllers\Inbox\DefaultInboxController;
use ActivityPub\Controllers\Outbox\DefaultOutboxController;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ControllerResolver implements ControllerResolverInterface
{
    private $objectService;
    private $inboxControllers;
    private $outboxControllers;

    public function __construct( ObjectsService $objectService )
    {
        $this->objectService = $objectService;
        $this->inboxControllers = array();
        $this->outboxControllers = array();
    }

    /**
     * Registers a new controller to handle ActivityPub inbox requests of type $type
     *
     * @param Callable $controller The controller
     * @param string $type The Activity type this controller can handle
     */
    public function registerInboxController( Callable $controller, string $type )
    {
        $this->inboxControllers[$type] = $controller;
    }

    /**
     * Registers a new controller to handle ActivityPub outbox requests of type $type
     *
     * @param Callable $controller The controller
     * @param string $type The Activity type this controller can handle
     */
    public function registerOutboxController( Callable $controller, string $type )
    {
        $this->outboxControllers[$type] = $controller;
    }
    
    /**
     * Returns true if an object with a field named $name with value $value exists
     *
     * @param string $name The field name to look for
     * @param string $value The field value to look for
     * @return bool
     */
    private function objectWithFieldExists( string $name, string $value )
    {
        return count( $this->objectService->query( array( $name => $value ) ) ) > 0;
    }

    public function getController( Request $request )
    {
        if ( $request->getMethod() == Request::METHOD_GET ) {
            $controller = new GetObjectController( $this->objectService );
            return array( $controller, 'handle' );
        } else if ( $request->getMethod() == Request::METHOD_POST ) {
            $uri = $request->getUri();
            if ( $this->objectWithFieldExists( 'inbox', $uri ) ) {
                $activity = json_decode( $request->getContent() );
                if ( ! isset( $activity->type ) ) {
                    throw new BadRequestHttpException( '"type" field not found' );
                }
                if ( array_key_exists( $activity->type, $this->inboxControllers ) ) {
                    return $this->inboxControllers[$activity->type];
                } else {
                    $controller = new DefaultInboxController( $this->objectService );
                    return array( $controller, 'handle' );
                }
            } else if ( $this->objectWithFieldExists( 'outbox', $uri ) ) {
                $activity = json_decode( $request->getContent() );
                if ( ! isset( $activity->type ) ) {
                    throw new BadRequestHttpException( '"type" field not found' );
                }
                if ( array_key_exists( $activity->type, $this->outboxControllers ) ) {
                    return $this->outboxControllers[$activity->type];
                } else {
                    $controller = new DefaultOutboxController( $this->objectService );
                    return array( $controller, 'handle' );
                }
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new MethodNotAllowedHttpException( array(
                Request::METHOD_GET,
                Request::METHOD_POST,
            ) );
        }
    }
}
?>
