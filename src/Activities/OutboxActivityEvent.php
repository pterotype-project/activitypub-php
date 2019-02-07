<?php
namespace ActivityPub\Activities;

use ActivityPub\Activities\ActivityEvent;

class OutboxActivityEvent extends ActivityEvent
{
    const NAME = 'outbox.activity';
}

