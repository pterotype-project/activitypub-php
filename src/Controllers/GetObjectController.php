<?php
namespace ActivityPub\Controllers;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
        if ( ! $this->requestAuthorizedToView( $request, $object ) ) {
            throw new UnauthorizedHttpException(
                'Signature realm="ActivityPub",headers="(request-target) host date"'
            );
        }
        if ( $object->hasField( 'type' ) &&
             ( $object['type'] === 'Collection' ||
               $object['type'] === 'OrderedCollection' ) ) {
            return $this->pageAndFilterCollection( $request, $object );
        }
        return new JsonResponse( $object->asArray() );
    }

    private function requestAuthorizedToView( Request $request,
                                              ActivityPubObject $object )
    {
        if ( ! $this->hasAudience( $object ) ) {
            return true;
        }
        $audience = $this->getAudience( $object );
        if ( in_array( 'https://www.w3.org/ns/activitystreams#Public', $audience ) ) {
            return true;
        }
        return $request->attributes->has( 'actor' ) &&
            in_array( $request->attributes->get( 'actor' ), $audience );
    }

    private function hasAudience( ActivityPubObject $object )
    {
        $arr = $object->asArray( 0 );
        return array_key_exists( 'audience', $arr ) ||
            array_key_exists( 'to', $arr ) ||
            array_key_exists( 'bto', $arr ) ||
            array_key_exists( 'cc', $arr ) ||
            array_key_exists( 'bcc', $arr );
    }

    /**
     * Returns an array of all of the $object's audience actors, i.e.
     * the contents of the to, bto, cc, bcc, and audience fields, as
     * well as the actor who created to object
     *
     * @param ActivityPubObject $object
     * @return array The audience members, collapsed to an array of ids
     */
    private function getAudience( ActivityPubObject $object )
    {
        // TODO do I need to traverse the inReplyTo chain here?
        $objectArr = $object->asArray( 0 );
        $audience = array();
        if ( array_key_exists( 'to', $objectArr ) ) {
            $audience = array_merge( $audience, $objectArr['to'] );
        }
        if ( array_key_exists( 'bto', $objectArr ) ) {
            $audience = array_merge( $audience, $objectArr['bto'] );
        }
        if ( array_key_exists( 'cc', $objectArr ) ) {
            $audience = array_merge( $audience, $objectArr['cc'] );
        }
        if ( array_key_exists( 'bcc', $objectArr ) ) {
            $audience = array_merge( $audience, $objectArr['bcc'] );
        }
        if ( array_key_exists( 'audience', $objectArr ) ) {
            $audience = array_merge( $audience, $objectArr['audience'] );
        }
        if ( array_key_exists( 'attributedTo', $objectArr ) ) {
            $audience[] = $objectArr['attributedTo']; 
        }
        if ( array_key_exists( 'actor', $objectArr ) ) {
            $audience[] = $objectArr['actor']; 
        }
        return $audience;
    }

    /**
     * Returns an array representation of the $collection
     *
     * If the collection's size is greater than 30, return a PagedCollection instead,
     * and filter all items by the request's permissions
     */
    private function pageAndFilterCollection( Request $request,
                                              ActivityPubObject $collection )
    {
        
    }
}
?>
