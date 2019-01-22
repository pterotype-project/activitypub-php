<?php
namespace ActivityPub\Controllers;

use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class OutboxController
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct( EventDispatcher $eventDispatcher )
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Handles the outbox request and returns a proper response
     *
     * @param Request $request The incoming request
     * @return Response
     */
    public function handle( Request $request )
    {
        $activity = $request->attributes->get( 'activity' );
        $outbox = $request->attributes->get( 'outbox' );
        $event = new OutboxActivityEvent( $activity, $outbox );
        $this->eventDispatcher->dispatch( OutboxActivityEvent::NAME, $event );
    }
}
?>
