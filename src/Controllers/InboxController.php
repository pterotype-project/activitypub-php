<?php
namespace ActivityPub\Controllers;

use ActivityPub\Activities\InboxActivityEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * The InboxController handles POST requests to an inbox
 */
class InboxController
{
    private $eventDispatcher;

    public function __construct( EventDispatcher $EventDispatcher )
    {
        $this->eventDispatcher = $EventDispatcher;
    }

    /**
     * Handles the inbox request and returns a proper Response
     *
     * @param Request $request The request
     * @return Response
     */
    public function handle( Request $request )
    {
        $activity = $request->attributes->get( 'activity' );
        $inbox = $request->attributes->get( 'inbox' );
        $event = new InboxActivityEvent( $activity, $inbox );
        $this->eventDispatcher->dispatch( InboxActivityEvent::NAME, $event );
    }
}
?>
