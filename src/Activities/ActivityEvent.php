<?php
namespace ActivityPub\Activities;

use ActivityPub\Entities\ActivityPubObject;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * The current request
     *
     * @var Request
     */
    protected $request;

    /**
     * The response
     *
     * @var Response
     */
    protected $response;

    public function __construct( array $activity, ActivityPubObject $actor,
                                    Request $request )
    {
        $this->activity = $activity;
        $this->actor = $actor;
        $this->request = $request;
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

    /**
     * @return Request The request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response The response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse( Response $response )
    {
        $this->response = $response;
    }
}

