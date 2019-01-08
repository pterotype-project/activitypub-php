<?php
namespace ActivityPub\Controllers\Inbox;

use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;

/**
 * The DefaultInboxController handles inbox requests not handled by other controllers
 */
class DefaultInboxController
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
