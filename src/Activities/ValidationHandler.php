<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ValidationHandler implements EventSubscriberInterface
{
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
    }

    public function verifyOutboxActivity( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $this->requireFields( $activity, array( 'type' ) );
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
