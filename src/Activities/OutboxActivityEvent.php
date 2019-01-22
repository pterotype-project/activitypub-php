<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\ActivityEvent;
use ActivityPub\Entities\ActivityPubObject;

class OutboxActivityEvent extends ActivityEvent
{
    const NAME = 'outbox.activity';

    /**
     * The outbox to which the activity was posted
     *
     * @var ActivityPubObject
     */
    protected $outbox;

    public function __construct( array $activity, ActivityPubObject $outbox )
    {
        parent::__construct( $activity );
        $this->outbox = $outbox;
    }

    /**
     * @return ActivityPubObject The outbox
     */
    public function getOutbox()
    {
        return $this->outbox;
    }
}
?>
