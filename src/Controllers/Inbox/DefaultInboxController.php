<?php
namespace ActivityPub\Controllers\Inbox;

use ActivityPub\Objects\ObjectService;
use Symfony\Component\HttpFoundation\Request;

/**
 * The DefaultInboxController handles inbox requests not handled by other controllers
 */
class DefaultInboxController
{
    private $objectService;

    public function __construct( ObjectService $objectService )
    {
        $this->objectService = $objectService;
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
