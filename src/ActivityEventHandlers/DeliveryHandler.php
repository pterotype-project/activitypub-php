<?php

namespace ActivityPub\ActivityEventHandlers;

use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionIterator;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\DateTimeProvider;
use ActivityPub\Utils\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeliveryHandler implements EventSubscriberInterface
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HttpSignatureService
     */
    private $signatureService;

    /**
     * @var DateTimeProvider
     */
    private $dateTimeProvider;

    public static function getSubscribedEvents()
    {
        return array(
            InboxActivityEvent::NAME => 'handleInboxForwarding',
            OutboxActivityEvent::NAME => 'deliverActivity',
        );
    }

    public function __construct( ObjectsService $objectsService,
                                 Client $httpClient,
                                 LoggerInterface $logger,
                                 HttpSignatureService $signatureService,
                                 DateTimeProvider $dateTimeProvider )
    {
        $this->objectsService = $objectsService;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->signatureService = $signatureService;
        $this->dateTimeProvider = $dateTimeProvider;
    }

    public function handleInboxForwarding( InboxActivityEvent $event )
    {
        // Forward the activity if:
        // - this is the first time we've seen the activity
        // - AND the values of to, cc, or audience contain a Collection that we own
        // - AND (according to Kaninii) if the 'object' of the activity is NOT an actor
        // - AND the values of inReplyTo, object, target, or tag are objects that we own, recursing through
        //       the objects in these value chains up to some reasonable limit
        $activity = $event->getActivity();
        if ( ! $event->getRequest()->attributes->get( 'firstTimeSeen' ) ) {
            $this->logger->debug(
                'Not forwarding activity because we\'ve seen it before', array( 'activity' => $activity )
            );
            return;
        }
        if ( array_key_exists( 'object', $activity ) && $this->isActor( $activity['object'] ) ) {
            $this->logger->debug(
                'Not forwarding activity with an actor as its object', array( 'activity' => $activity )
            );
            return;
        }
        $forwardingTargets = array();
        $recipients = array_intersect( $activity, array_flip( array( 'to', 'cc', 'audience' ) ) );
        foreach ( $recipients as $recip ) {
            $recipId = $recip;
            if ( is_array( $recipId ) && array_key_exists( 'id', $recipId ) ) {
                $recipId = $recipId['id'];
            }
            if ( is_string( $recipId ) ) {
                $recipient = $this->objectsService->dereference( $recipId );
                if (
                    $recipient->hasField( 'type' ) &&
                    in_array( $recipient['type'], array( 'Collection', 'OrderedCollection') ) &&
                    Util::isLocalUri( $recipient['id'] )
                ) {
                    $forwardingTargets = array_unique( array_merge(
                        $forwardingTargets, $this->resolveRecipient( $recipient )
                    ) );
                }
            }
        }
        if ( count( $forwardingTargets ) === 0 ) {
            $this->logger->debug(
                'No collections we own in recipients, not forwarding', array( 'activity' => $activity )
            );
            return;
        }
        // TODO recurse through inReplyTo, object, target, and tags looking for object we own
    }

    public function deliverActivity( OutboxActivityEvent $event )
    {
        $activity = $event->getActivity();
        $recipientFields = array( 'to', 'bto', 'cc', 'bcc', 'audience' );
        $inboxes = array();
        foreach ( $recipientFields as $field ) {
            if ( array_key_exists( $field, $activity ) ) {
                $recipients = $activity[$field];
                if ( ! is_array( $recipients ) ) {
                    $recipients = array( $recipients );
                }
                foreach ( $recipients as $recipient ) {
                    if ( is_array( $recipient ) && array_key_exists( 'id', $recipient ) ) {
                        $recipient = $recipient['id'];
                    }
                    if ( is_string( $recipient ) ) {
                        $recipientObj = $this->objectsService->dereference( $recipient );
                        $inboxes = array_merge( $inboxes, $this->resolveRecipient( $recipientObj ) );
                    }
                }
            }
        }
        $inboxes = array_unique( $inboxes );
        $activityActor = $activity['actor'];
        if ( is_array( $activityActor ) && array_key_exists( 'id', $activityActor ) ) {
            $activityActor = $activityActor['id'];
        }
        if ( is_string( $activityActor ) ) {
            $inboxes = array_diff( $inboxes, array( $activityActor ) );
        }
        foreach ( array( 'bto', 'bcc' ) as $privateField ) {
            if ( array_key_exists( $privateField, $activity ) ) {
                unset( $activity[$privateField] );
            }
        }
        $actor = $event->getReceivingActor();
        $requestPromises = array();
        foreach ( $inboxes as $inbox ) {
            $headers = array(
                'Content-Type' => 'application/ld+json',
                'Date' => $this->dateTimeProvider->getTime( 'delivery-handler.deliver' ),
            );
            $request = new Request( 'POST', $inbox, $headers );
            $publicKeyId = $actor['publicKey'];
            if ( $publicKeyId instanceof ActivityPubObject ) {
                $publicKeyId = $publicKeyId['id'];
            }
            if ( $actor->hasPrivateKey() && is_string( $publicKeyId ) ) {
                $signature = $this->signatureService->sign( $request, $actor->getPrivateKey(), $publicKeyId );
                $request = $request->withHeader( 'Signature', $signature );
            } else {
                $this->logger->warning(
                    'Unable to find a keypair for actor; delivering without signature',
                    array( 'actorId' => $actor['id'] )
                );
            }
            $requestPromises[$inbox] = $this->httpClient->sendAsync( $request, array( 'timeout' => 3 ) );
        }
        $responses = \GuzzleHttp\Promise\settle( $requestPromises )->wait();
        foreach ( $responses as $inbox => $response ) {
            if ( $response['state'] === 'rejected' ) {
                $e = $response['reason'];
                $this->logger->error(
                    'Error delivering activity',
                    array( 'inboxUri' => $inbox, 'errorMessage' => $e->getMessage() )
                );
            }
        }
    }

    /**
     * Given an ActivityPubObject to deliver to, returns an array of inbox URLs
     * @param ActivityPubObject $recipient
     * @param int $depth The depth to which we will unpack nested collection references
     * @return array
     */
    private function resolveRecipient( ActivityPubObject $recipient, $depth = 5 )
    {
        if ( $depth < 0 ) {
            return array();
        }
        if ( $recipient && $recipient->hasField( 'inbox' ) ) {
            $inbox = $recipient['inbox'];
            if ( $inbox instanceof ActivityPubObject && $inbox->hasField( 'id' ) ) {
                $inbox = $inbox['id'];
            }
            if ( is_string( $inbox ) ) {
                return array( $inbox );
            }
        } else if (
            $recipient &&
            $recipient->hasField( 'type' ) &&
            in_array( $recipient['type'], array( 'Collection', 'OrderedCollection' ) )
        ) {
            $inboxes = array();
            foreach ( CollectionIterator::iterateCollection( $recipient ) as $item ) {
                if ( $item instanceof ActivityPubObject ) {
                    $inboxes = array_unique( array_merge( $inboxes, $this->resolveRecipient( $item, $depth - 1 ) ) );
                }
            }
            return $inboxes;
        }
        return array();
    }

    private function isActor( $objectId )
    {
        if ( is_array( $objectId ) && array_key_exists( 'id', $objectId ) ) {
            $objectId = $objectId['id'];
        }
        if ( ! is_string( $objectId ) ) {
            return false;
        }
        $object = $this->objectsService->dereference( $objectId );
        if ( ! $object ) {
            return false;
        }
        return $object->hasField( 'inbox' ) && $object->hasField( 'outbox' );
    }
}