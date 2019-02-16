<?php

namespace ActivityPub\Activities;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\ContextProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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

    public function __construct( ContextProvider $contextProvider )
    {
        $this->contextProvider = $contextProvider;
    }

    public static function getSubscribedEvents()
    {
        return array(
            OutboxActivityEvent::NAME => 'handle',
        );
    }

    public function handle( OutboxActivityEvent $event )
    {
        $object = $event->getActivity();
        if ( in_array( $object['type'], self::activityTypes() ) ) {
            return;
        }
        $actor = $event->getActor();
        $create = $this->makeCreate( $object, $actor );
        $event->setActivity( $create );
    }

    public static function activityTypes()
    {
        return array(
            'Accept', 'Add', 'Announce', 'Arrive',
            'Block', 'Create', 'Delete', 'Dislike',
            'Flag', 'Follow', 'Ignore', 'Invite',
            'Join', 'Leave', 'Like', 'Listen',
            'Move', 'Offer', 'Question', 'Reject',
            'Read', 'Remove', 'TentativeReject', 'TentativeAccept',
            'Travel', 'Undo', 'Update', 'View',
        );
    }

    /**
     * Makes a new Create activity with $object as the object
     *
     * @param array $object The object
     * @param ActivityPubObject $actor The actor creating the object
     *
     * @return array The Create activity
     */
    private function makeCreate( array $object,
                                 ActivityPubObject $actor )
    {
        $create = array(
            '@context' => $this->contextProvider->getContext(),
            'type' => 'Create',
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

