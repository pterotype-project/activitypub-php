<?php
namespace ActivityPub\Controllers;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;

/**
 * The InboxController handles POST requests to an inbox
 */
class InboxController
{
    private $ObjectsService;

    public function __construct( ObjectsService $ObjectsService )
    {
        $this->ObjectsService = $ObjectsService;
    }

    /**
     * Handles the inbox request and returns a proper Response
     *
     * @param Request $request The request
     * @return Response
     */
    public function handle( Request $request )
    {
        // TODO implement me
    }
}
?>
