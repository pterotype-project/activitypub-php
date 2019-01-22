<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The NonActivityHandler wraps non-activity objects sent to the outbox in a Create activity
 */
class NonActivityHandler implements EventSubscriberInterface
{
    /**
     * @var ContextProvider
     */
    private $contextProvider;
    
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

    public function handle( OutboxActivityEvent $event )
    {
        $object = $event->getActivity();
        if ( in_array( $object['type'], self::ACTIVITY_TYPES ) ) {
            return;
        }
        $create = $this->makeCreate( $object );
        $event->setActivity( $create );
    }

    /**
     * Makes a new Create activity with $object as the object
     *
     * @return array The Create activity
     */
    private function makeCreate( array $object )
    {
        // TODO implement me
        // if object doesn't have an id, generate one
        // generate an id for the Create activity as well
    }
}
?>
