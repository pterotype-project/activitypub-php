<?php
namespace ActivityPub\Actors;

use ActivityPub\Actors\ActivityPubActor;
use ActivityPub\Entities\ActivityPubObject;

/**
 * The Inbox class represents an ActivityPub inbox. It provides methods
 * to receive activities in an actor's inbox.
 */
class Inbox
{
    /**
     * The ActivityPubObject that represents this inbox
     *
     * @var ActivityPubObject
     */
    private $object;

    /**
     * The actor to which this inbox belongs
     *
     * @var ActivityPubActor
     */
    private $actor;

    /**
     * Constructs a new Inbox
     *
     * @param ActivityPubActor $actor The actor to which the inbox belongs
     * @param ActivityPubObject $object The ActivityPubObject that represents the inbox
     */
    public function __construct( ActivityPubActor $actor, ActivityPubObject $object )
    {
        $this->actor = $actor;
        $this->object = $object;
    }

    /**
     * Receives a new activity to the inbox, verifying the provided signature.
     *
     * @param ActivityPubObject $activity The received activity
     * @param string $signature A valid signature for the activity, signed with
     *   the the private key of the actor who created it
     */
    public function receive( ActivityPubObject $activity, string signature )
    {
        // TODO
    }
}
?>
