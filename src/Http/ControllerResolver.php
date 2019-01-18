<?php
namespace ActivityPub\Http;

use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Controllers\InboxController;
use ActivityPub\Controllers\OutboxController;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ControllerResolver implements ControllerResolverInterface
{
    private $objectsService;
    private $getObjectController;
    private $inboxController;
    private $outboxController;

    public function __construct( ObjectsService $objectsService,
                                 GetObjectController $getObjectController,
                                 InboxController $inboxController,
                                 OutboxController $outboxController )
    {
        $this->objectsService = $objectsService;
        $this->getObjectController = $getObjectController;
        $this->inboxController = $inboxController;
        $this->outboxController = $outboxController;
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
        return count( $this->objectsService->query( array( $name => $value ) ) ) > 0;
    }

    public function getController( Request $request )
    {
        if ( $request->getMethod() == Request::METHOD_GET ) {
            return array( $this->getObjectController, 'handle' );
        } else if ( $request->getMethod() == Request::METHOD_POST ) {
            $uri = $request->getUri();
            if ( $this->objectWithFieldExists( 'inbox', $uri ) ) {
                $activity = json_decode( $request->getContent() );
                if ( ! isset( $activity->type ) ) {
                    throw new BadRequestHttpException( '"type" field not found' );
                }
                return array( $this->inboxController, 'handle' );
            } else if ( $this->objectWithFieldExists( 'outbox', $uri ) ) {
                $activity = json_decode( $request->getContent() );
                if ( ! isset( $activity->type ) ) {
                    throw new BadRequestHttpException( '"type" field not found' );
                }
                return array( $this->outboxController, 'handle' );
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
