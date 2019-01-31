<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\ActivityEvent;
use ActivityPub\Activities\InboxActivityEvent;
use ActivityPub\Activities\OutboxActivityEvent;
use ActivityPub\Utils\DateTimeProvider;
use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteHandler implements EventSubscriberInterface
{
    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleDelete',
            OutboxActivityEvent::NAME => 'handleDelete',
        );
    }

    public function __construct( DateTimeProvider $dateTimeProvider,
                                 ObjectsService $objectsService )
    {
        $this->dateTimeProvider = $dateTimeProvider;
        $this->objectsService = $objectsService;
    }

    private function handleDelete( ActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Delete' ) {
            return;
        }
        $objectId = $activity['object'];
        if ( ! is_string( $objectId ) ) {
            if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
                $objectId = $objectId['id'];
            } else {
                throw new BadRequestHttpException( 'Object must have an "id" field' );
            }
        }
        $tombstone = array(
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Tombstone',
            'deleted' => $this->getNowTimestamp(),
        );
        $existing = $this->objectsService->dereference( $objectId );
        if ( $existing ) {
            $tombstone['formerType'] = $existing['type'];
        }
        $this->objectsService->replace( $objectId, $tombstone );
    }

    private function getNowTimestamp()
    {
        return $this->dateTimeProvider->getTime( 'activities.delete' )
            ->format( DateTime::ISO8601 );
    }
}
?>
