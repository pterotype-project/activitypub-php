<?php
namespace ActivityPub\Controllers;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;

class OutboxController
{
    private $objectsService;

    public function __construct( ObjectsService $objectsService )
    {
        $this->objectsService = $objectsService;
    }

    /**
     * Handles the outbox request and returns a proper response
     *
     * @param Request $request The incoming request
     * @return Response
     */
    public function handle( Request $request )
    {
        // TODO implement me
    }
}
?>
