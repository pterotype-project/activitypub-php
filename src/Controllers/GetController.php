<?php
namespace ActivityPub\Controllers;

use ActivityPub\Auth\AuthService;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * The GetController is responsible for rendering ActivityPub objects as JSON
 */
class GetController
{
    
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var CollectionsService
     */
    private $collectionsService;

    /**
     * @var AuthService
     */
    private $authService;

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService,
                                 AuthService $authService )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
        $this->authService = $authService;
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
        $queryPos = strpos( $uri, '?' );
        if ( $queryPos !== false ) {
            $uri = substr( $uri, 0, $queryPos );
        }
        $object = $this->objectsService->dereference( $uri );
        if ( ! $object ) {
            throw new NotFoundHttpException();
        }
        if ( ! $this->authService->isAuthorized( $request, $object ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        if ( $object->hasField( 'type' ) &&
             ( $object['type'] === 'Collection' ||
               $object['type'] === 'OrderedCollection' ) ) {
            return $this->collectionsService->pageAndFilterCollection( $request, $object );
        }
        $response = new JsonResponse( $object->asArray() );
        if ( $object->hasField( 'type' ) &&
             $object['type'] === 'Tombstone' ) {
            $response->setStatusCode( 410 );
        }
        return $response;
    }
}

