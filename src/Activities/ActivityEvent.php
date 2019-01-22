<?php
namespace ActivityPub\Activities;

use Symfony\Component\EventDispatcher\Event;

class ActivityEvent extends Event
{
    /**
     * The activity that was received
     *
     * @var array
     */
    protected $activity;

    protected function __construct( array $activity )
    {
        $this->activity = $activity;
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
}
?>
