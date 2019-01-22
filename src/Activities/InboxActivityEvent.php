<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\ActivityEvent;
use ActivityPub\Entities\ActivityPubObject;

class InboxActivityEvent extends ActivityEvent
{
    const NAME = 'inbox.activity';

    /**
     * The inbox to which the activity was posted
     *
     * @var ActivityPubObject
     */
    protected $inbox;

    public function __construct( array $activity, ActivityPubObject $inbox )
    {
        parent::__construct( $activity );
        $this->inbox = $inbox;
    }

    /**
     * @return ActivityPubObject The inbox
     */
    public function getInbox()
    {
        return $this->inbox;
    }
}
?>
