<?php
namespace ActivityPub\Actors;

use ActivityPub\Crypto\RsaKeypair;
use ActivityPub\Entities\ActivityPubObject;

/**
 * Represents an ActivityPub actor object
 *
 * This class is the main entrypoint for the ActivityPub API, via the
 * inbox() and outbox() methods.
 */
class ActivityPubActor
{
    /**
     * The ActivityPubObject that represents this actor
     *
     * @var ActivityPubObject
     */
    private $object;

    /**
     * The actor's inbox
     *
     * @var Inbox
     */
    private $inbox;

    /**
     * The actor's outbox
     *
     * @var Outbox
     */
    private $outbox;

    /**
     * The actor's RSA keypair
     *
     * @var RsaKeypair
     */
    private $keypair;

    /**
     * Constructs a new actor
     *
     * @param ActivityPubObject $object The ActivityPubObject that represents the actor
     * @param RsaKeypair $keypair The actor's RSA keypair
     */
    public function __construct( ActivityPubObject $object,
                                    RsaKeypair $keypair )
    {
        if ( ! $object['inbox'] || ! $object['outbox'] ) {
            throw new InvalidArgumentException(
                'Attempted to construct an ActivityPubActor without an inbox or outbox'
            );
        }
        $this->object = $object;
        $this->keypair = $keypair;
        $this->inbox = new Inbox( $this, $object['inbox'] );
        $this->outbox = new Outbox( $this, $object['outbox'] );
    }

    /**
     * Returns the actor's inbox
     *
     * @return Inbox
     */
    public function inbox()
    {
        return $this->inbox;
    }

    /**
     * Returns the actor's outbox
     *
     * @return Outbox
     */
    public function outbox()
    {
        return $this->outbox;
    }

    /**
     * Returns the actor's keypair
     *
     * @return RsaKeypair
     */
    public function keypair()
    {
        return $this->keypair;
    }
}
?>
