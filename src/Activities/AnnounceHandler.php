<?php

namespace ActivityPub\Activities;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AnnounceHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var CollectionsService
     */
    private $collectionsService;

    /**
     * @var ContextProvider
     */
    private $contextProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
        );
    }

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService,
                                 ContextProvider $contextProvider )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
        $this->contextProvider = $contextProvider;
    }

    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Announce' ) {
            return;
        }
        $objectId = $activity['object'];
        if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
            $objectId = $objectId['id'];
        }
        if ( ! is_string( $objectId ) ) {
            throw new BadRequestHttpException( 'Invalid object' );
        }
        $object = $this->objectsService->dereference( $objectId );
        if ( ! $object->hasField( 'shares' ) ) {
            $object = $this->addCollectionToObject( $object, 'shares' );

        }
        $shares = $object['shares'];
        $this->collectionsService->addItem( $shares, $activity );
    }

    private function addCollectionToObject( ActivityPubObject $object, $collectionName )
    {
        $updatedObject = $object->asArray();
        $updatedObject[$collectionName] = array(
            '@context' => $this->contextProvider->getContext(),
            'id' => rtrim( $updatedObject['id'], '/' ) . '/' . $collectionName,
            'type' => 'Collection',
            'items' => array(),
        );
        return $this->objectsService->update( $object['id'], $updatedObject );
    }
}