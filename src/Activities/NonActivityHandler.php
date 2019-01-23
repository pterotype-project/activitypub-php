<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\IdProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The NonActivityHandler wraps non-activity objects sent to the outbox in a
 * Create activity
 */
class NonActivityHandler implements EventSubscriberInterface
{
    /**
     * @var ContextProvider
     */
    private $contextProvider;

    /**
     * @var IdProvider
     */
    private $idProvider;
    
    const ACTIVITY_TYPES = array(
        'Accept', 'Add', 'Announce', 'Arrive',
        'Block', 'Create', 'Delete', 'Dislike',
        'Flag', 'Follow', 'Ignore', 'Invite',
        'Join', 'Leave', 'Like', 'Listen',
        'Move', 'Offer', 'Question', 'Reject',
        'Read', 'Remove', 'TentativeReject', 'TentativeAccept',
        'Travel', 'Undo', 'Update', 'View',
    );
    
    public static function getSubscribedEvents()
    {
        return array(
            OutboxActivityEvent::NAME => 'handle',
        );
    }

    public function __construct( ContextProvider $contextProvider,
                                 IdProvider $idProvider )
    {
        $this->contextProvider = $contextProvider;
        $this->idProvider = $idProvider;
    }

    public function handle( OutboxActivityEvent $event )
    {
        $object = $event->getActivity();
        if ( in_array( $object['type'], self::ACTIVITY_TYPES ) ) {
            return;
        }
        $request = $event->getRequest();
        $actor = $event->getActor();
        $create = $this->makeCreate( $request, $object, $actor );
        $event->setActivity( $create );
    }

    /**
     * Makes a new Create activity with $object as the object
     *
     * @param Request $request The current request
     * @param array $object The object
     * @param ActivityPubObject $actorId The actor creating the object
     *
     * @return array The Create activity
     */
    private function makeCreate( Request $request, array $object,
                                 ActivityPubObject $actor )
    {
        $create = array(
            '@context' => $this->contextProvider->getContext(),
            'type' => 'Create',
            'id' => $this->idProvider->getId( $request, "activities" ),
            'actor' => $actor['id'],
            'object' => $object,
        );
        foreach ( array( 'to', 'bto', 'cc', 'bcc', 'audience' ) as $field ) {
            if ( array_key_exists( $field, $object ) ) {
                $create[$field] = $object[$field];
            }
        }
        return $create;
    }
}
?>
