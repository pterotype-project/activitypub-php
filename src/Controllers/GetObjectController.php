<?php
namespace ActivityPub\Controllers;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The GetObjectController is responsible for rendering ActivityPub objects as JSON
 */
class GetObjectController
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( ObjectsService $objectsService )
    {
        $this->objectsService = $objectsService;
    }

    /**
     * Returns a Response with the JSON representation of the requested object
     *
     * @param Request $request The HTTP request
     * @return Response
     */
    public function handle( Request $request )
    {
        $uri = $request->getUri();
        $object = $this->objectsService->dereference( $uri );
        if ( ! $object ) {
            throw new NotFoundHttpException();
        }
        return new JsonResponse( $object->asArray() );
    }
}
?>
