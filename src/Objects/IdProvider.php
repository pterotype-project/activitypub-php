<?php
namespace ActivityPub\Objects;

use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\RandomProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * The IdProvider provides methods to generate new unique ids for objects
 */
class IdProvider
{
    const ID_LENGTH = 12;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var RandomProvider
     */
    private $randomProvider;

    public function __construct( ObjectsService $objectsService, RandomProvider $randomProvider )
    {
        $this->objectsService = $objectsService;
        $this->randomProvider = $randomProvider;
    }

    /**
     * Generates a new unique ActivityPub id
     *
     * Ids look like "https://$host/$path/$randomString"
     * @param Request $request The current request
     * @param string $path The path for the the id URL, just before the random string
     *   Default: "object"
     * @return string The new id
     */
    public function getId( Request $request, string $path = "objects" )
    {
        $baseUri = $request->getSchemeAndHttpHost();
        if ( ! empty( $path ) ) {
            $baseUri = $baseUri . "/$path";
        }
        $rnd = $this->randomProvider->randomString( self::ID_LENGTH );
        $id = $baseUri . "/$rnd";
        $existing = $this->objectsService->query( array( 'id' => $id ) );
        while ( count( $existing ) > 0 ) {
            $rnd = $this->randomProvider->randomString( self::ID_LENGTH );
            $id = $baseUri . "/$rnd";
            $existing = $this->objectsService->query( array( 'id' => $id ) );
        }
        return $id;
    }
}
?>
