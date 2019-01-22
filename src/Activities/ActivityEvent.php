<?php
namespace ActivityPub\Activities;

use ActivityPub\Entities\ActivityPubObject;
use Symfony\Component\EventDispatcher\Event;

class ActivityEvent extends Event
{
    /**
     * The activity that was received
     *
     * @var array
     */
    protected $activity;

    /**
     * The actor posting or receiving the activity
     *
     * @var ActivityPubObject
     */
    protected $actor;

    protected function __construct( array $activity, ActivityPubObject $actor )
    {
        $this->activity = $activity;
        $this->actor = $actor;
    }

    /**
     * @return array The activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    public function setActivity( array $activity )
    {
        $this->activity = $activity;
    }

    /**
     * @return ActivityPubObject The actor
     */
    public function getActor()
    {
        return $this->actor;
    }
}
?>
