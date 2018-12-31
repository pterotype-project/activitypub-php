<?php
namespace ActivityPub\Actors;

use ActivityPub\Actors\Actor;
use ActivityPub\Entities\ActivityPubObject;

/**
 * The Outbox class represents an ActivityPub outbox. It provides methods
 * to post activities to an actor's outbox, and takes care of signing and
 * delivering posted activities.
 */
class Outbox
{
    /**
     * The ActivityPubObject that represents this outbox
     *
     * @var ActivityPubObject
     */
    private $object;

    /**
     * The actor to which this outbox belongs
     *
     * @var Actor
     */
    private $actor;

    /**
     * Constructs a new Outbox
     *
     * @param Actor $actor The actor to which the outbox belongs
     * @param ActivityPubObject $object The ActivityPubObject that represents the outbox 
     */
    public function __construct( Actor $actor, ActivityPubObject $object )
    {
        $this->actor = $actor;
        $this->object = $object;
    }

    /**
     * Posts a new activity to the outbox, signing and delivering the activity
     * to the correct recipients.
     *
     * @param ActivityPubObject $activity The activity
     */
    public function post( ActivityPubObject $activity )
    {
        // TODO
    }
}
?>
