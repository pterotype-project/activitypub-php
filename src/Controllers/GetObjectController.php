<?php
namespace ActivityPub\Controllers;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;

/**
 * The GetObjectController is responsible for rendering ActivityPub objects as JSON
 */
class GetObjectController
{
    private $objectService;

    public function __construct( ObjectService $objectService )
    {
        $this->objectService = $objectService;
    }

    /**
     * Returns a Response with the JSON representation of the requested object
     *
     * @param Request $request The HTTP request
     * @return Response
     */
    public function handle( Request $request )
    {
        // TODO implement me
    }
}
?>
