<?php

namespace ActivityPub\Controllers;

use ActivityPub\Auth\AuthService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\BlockService;
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

    /**
     * @var BlockService
     */
    private $blockService;

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService,
                                 AuthService $authService,
                                 BlockService $blockService )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
        $this->authService = $authService;
        $this->blockService = $blockService;
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
        if ( !$object ) {
            throw new NotFoundHttpException();
        }
        if ( !$this->authService->isAuthorized( $request, $object ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        if ( $object->hasField( 'type' ) &&
            ( $object['type'] === 'Collection' ||
                $object['type'] === 'OrderedCollection' ) ) {
            if ( $object->hasReferencingField( 'inbox' ) ) {
                // TODO figure out what to pass in here
                $blockedActorIds = $this->blockService->getBlockedActorIds();
                $filterFunc = function ( ActivityPubObject $item ) use ( $request, $blockedActorIds ) {
                    $authorized = $this->authService->isAuthorized( $request, $item );
                    foreach ( array( 'actor', 'attributedTo' ) as $actorField ) {
                        if ( $item->hasField( $actorField ) ) {
                            $actorFieldValue = $item->getFieldValue( $actorField );
                            if ( ! $actorFieldValue ) {
                                continue;
                            }
                            if ( is_string( $actorFieldValue &&
                                in_array( $actorFieldValue, $blockedActorIds ) ) ) {
                                $authorized = false;
                                break;
                            } else if ( $actorFieldValue instanceof ActivityPubObject &&
                            in_array( $actorFieldValue['id'], $blockedActorIds ) ) {
                                $authorized = false;
                                break;
                            }
                        }
                    }
                    return $authorized;
                }
            } else {
                $filterFunc = function ( ActivityPubObject $item ) use ( $request ) {
                    return $this->authService->isAuthorized( $request, $item );
                };
            }
            $pagedCollection = $this->collectionsService->pageAndFilterCollection( $request, $object, $filterFunc );

            return new JsonResponse( $pagedCollection );
        }
        $response = new JsonResponse( $object->asArray() );
        if ( $object->hasField( 'type' ) &&
            $object['type'] === 'Tombstone' ) {
            $response->setStatusCode( 410 );
        }
        return $response;
    }
}

