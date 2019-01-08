<?php
namespace ActivityPub\Controllers\Outbox;

use ActivityPub\Objects\ObjectService;
use Symfony\Component\HttpFoundation\Request;

class DefaultOutboxController
{
    private $objectService;

    public function __construct( ObjectService $objectService )
    {
        $this->objectService = $objectService;
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
