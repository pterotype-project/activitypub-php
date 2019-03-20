<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcceptHandler implements EventSubscriberInterface
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

    public function __construct( ObjectsService $objectsService,
                                 CollectionsService $collectionsService,
                                 ContextProvider $contextProvider )
    {
        $this->objectsService = $objectsService;
        $this->collectionsService = $collectionsService;
        $this->contextProvider = $contextProvider;
    }

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInbox',
            OutboxActivityEvent::NAME => 'handleOutbox',
        );
    }

    public function handleInbox( InboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Accept' ) {
            return;
        }
        $localActor = $event->getActor();
        $followId = $activity['object'];
        if ( is_array( $followId ) && array_key_exists( 'id', $followId ) ) {
            $followId = $followId['id'];
        }
        if ( ! is_string( $followId ) ) {
            return;
        }
        $follow = $this->objectsService->dereference( $followId );
        if ( ! $follow ) {
            return;
        }
        if ( ! ( $follow->hasField( 'actor') && $localActor->equals( $follow['actor'] ) ) ) {
            return;
        }
        $remoteActor = $event->getRequest()->attributes->get('actor');
        if ( ! $remoteActor->equals( $follow['object'] ) ) {
            return;
        }
        if ( $localActor->hasField( 'following' ) ) {
            $following = $localActor['following'];
        } else {
            $updatedLocalActor = $localActor->asArray();
            $updatedLocalActor['following'] = array(
                '@context' => $this->contextProvider->getContext(),
                'id' => rtrim( $updatedLocalActor['id'], '/' ) . '/following',
                'type' => 'Collection',
                'items' => array(),
            );
            $localActor = $this->objectsService->update( $localActor['id'], $updatedLocalActor );
            $following = $localActor['following'];
        }
        $newFollowing = $follow['object'];
        $this->collectionsService->addItem( $following, $newFollowing->asArray() );
    }

    public function handleOutbox( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        if ( $activity['type'] !== 'Accept' ) {
            return;
        }
        $request = $event->getRequest();
        // either there is a 'follow' key on the request,
        // in which case this is an auto-accept dispatched from
        // the FollowHandler so the Follow won't be in the database yet,
        // or there isn't, in which case this is an ordinary Accept
        // sent by a client and the Follow is in the database
        $follow = $request->attributes->get( 'follow' );
        if ( !$follow ) {
            $followId = $activity['object'];
            if ( is_array( $followId ) && array_key_exists( 'id', $followId ) ) {
                $followId = $followId['id'];
            }
            if ( ! is_string( $followId ) ) {
                return;
            }
            $follow = $this->objectsService->dereference( $followId );
            if ( ! $follow ) {
                return;
            }
            $follow = $follow->asArray();
        }
        if ( !$follow || !array_key_exists( 'object', $follow ) ) {
            return;
        }
        $followObjectId = $follow['object'];
        if ( is_array( $followObjectId ) && array_key_exists( 'id', $followObjectId ) ) {
            $followObjectId = $followObjectId['id'];
        }
        $localActor = $event->getActor();
        if ( $followObjectId !== $localActor['id'] ) {
            return;
        }
        $followers = $localActor['followers'];
        if ( ! $followers ) {
            $updatedLocalActor = $localActor->asArray();
            $updatedLocalActor['followers'] = array(
                '@context' => $this->contextProvider->getContext(),
                'id' => rtrim( $updatedLocalActor['id'], '/' ) . '/followers',
                'type' => 'Collection',
                'items' => array(),
            );
            $localActor = $this->objectsService->update( $localActor['id'], $updatedLocalActor );
            $followers = $localActor['followers'];
        }
        $this->collectionsService->addItem( $followers, $follow['actor'] );
    }
}
