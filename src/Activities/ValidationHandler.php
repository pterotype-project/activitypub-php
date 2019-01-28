<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ValidationHandler implements EventSubscriberInterface
{
    const OBJECT_REQUIRED_TYPES = array(
        'Create', 'Update', 'Delete', 'Follow',
        'Add', 'Remove', 'Like', 'Block', 'Undo',
    );

    const TARGET_REQUIRED_TYPES = array(
        'Add', 'Remove',
    );

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'verifyInboxActivity',
            OutboxActivityEvent::NAME => 'verifyOutboxActivity',
        );
    }

    public function verifyInboxActivity( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $this->requireFields( $activity, array( 'type', 'id', 'actor' ) );
        if ( in_array( $activity['type'], self::OBJECT_REQUIRED_TYPES ) ) {
            $this->requireFields( $activity, array( 'object' ) );
        }
        if ( in_array( $activity['type'], self::TARGET_REQUIRED_TYPES ) ) {
            $this->requireFields( $activity, array( 'target' ) );
        }
    }

    public function verifyOutboxActivity( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $this->requireFields( $activity, array( 'type' ) );
        if ( in_array( $activity['type'], self::OBJECT_REQUIRED_TYPES ) ) {
            $this->requireFields( $activity, array( 'object' ) );
        }
        if ( in_array( $activity['type'], self::TARGET_REQUIRED_TYPES ) ) {
            $this->requireFields( $activity, array( 'target' ) );
        }
    }

    private function requireFields( array $activity, array $fields )
    {
        $missing = array();
        foreach ( $fields as $field ) {
            if ( ! array_key_exists( $field, $activity ) ) {
                $missing[] = $field;
            }
        }
        if ( count( $missing ) > 0 ) {
            throw new BadRequestHttpException(
                "Missing activity fields: " . implode( ',', $missing )
            );
        }
    }
}
?>
