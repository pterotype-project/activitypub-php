<?php

namespace ActivityPub\Activities;

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
        $requiredFields = array( 'type', 'id', 'actor' );
        if ( array_key_exists( 'type', $activity ) &&
            in_array( $activity['type'], self::getObjectRequiredTypes() ) ) {
            $requiredFields[] = 'object';
        }
        if ( array_key_exists( 'type', $activity ) &&
            in_array( $activity['type'], self::getTargetRequiredTypes() ) ) {
            $requiredFields[] = 'target';
        }
        $this->requireFields( $activity, $requiredFields );
    }

    public static function getObjectRequiredTypes()
    {
        return array(
            'Create', 'Update', 'Delete', 'Follow',
            'Add', 'Remove', 'Like', 'Block', 'Undo',
        );
    }

    public static function getTargetRequiredTypes()
    {
        return array(
            'Add', 'Remove',
        );
    }

    private function requireFields( array $activity, array $fields )
    {
        $missing = array();
        foreach ( $fields as $field ) {
            if ( !array_key_exists( $field, $activity ) ) {
                $missing[] = $field;
            }
        }
        if ( count( $missing ) > 0 ) {
            throw new BadRequestHttpException(
                "Missing activity fields: " . implode( ',', $missing )
            );
        }
    }

    public function verifyOutboxActivity( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $requiredFields = array( 'type', 'actor' );
        if ( array_key_exists( 'type', $activity ) &&
            in_array( $activity['type'], self::getObjectRequiredTypes() ) ) {
            $requiredFields[] = 'object';
        }
        if ( array_key_exists( 'type', $activity ) &&
            in_array( $activity['type'], self::getTargetRequiredTypes() ) ) {
            $requiredFields[] = 'target';
        }
        $this->requireFields( $activity, $requiredFields );
    }
}

