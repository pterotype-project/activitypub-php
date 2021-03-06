<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\CreateHandler;
use ActivityPub\ActivityEventHandlers\InboxActivityEvent;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Auth\AuthService;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class CreateHandlerTest extends APTestCase
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = new EventDispatcher();
        $objectsService = $this->getMock( ObjectsService::class );
        $idProvider = $this->getMock( IdProvider::class );
        // TODO provision mocks
        $collectionsService = new CollectionsService(
            4,
            $this->getMock( AuthService::class ),
            new ContextProvider(),
            $this->getMock( Client::class ),
            new SimpleDateTimeProvider(),
            $this->getMock( EntityManager::class ),
            $objectsService
        );
        $createHandler = new CreateHandler(
            $objectsService, $idProvider, $collectionsService
        );
        $this->eventDispatcher->addSubscriber( $createHandler );
    }

    public function provideTestCreateHandler()
    {
        return array(
            array( array(
                'id' => 'basicInboxTest',
                'eventName' => InboxActivityEvent::NAME,
                'event' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Create',
                        'actor' => 'https://elsewhere.com/actors/1',
                        'object' => array(
                            'id' => 'https://elsewhere.com/objects/1',
                            'type' => 'Note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://elsewhere.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/inbox', Request::METHOD_POST )
                ),
                'expectedEvent' => new InboxActivityEvent(
                    array(
                        'id' => 'https://elsewhere.com/activities/1',
                        'type' => 'Create',
                        'actor' => 'https://elsewhere.com/actors/1',
                        'object' => array(
                            'id' => 'https://elsewhere.com/objects/1',
                            'type' => 'Note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://elsewhere.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/inbox', Request::METHOD_POST )
                ),
            ) ),
            array( array(
                'id' => 'basicOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1'
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
                'expectedEvent' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'attributedTo' => 'https://example.com/actors/1'
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
            ) ),
            array( array(
                'id' => 'copiesAudienceOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        'cc' => array(
                            'https://elsewhere.com/actors/2'
                        ),
                        'audience' => 'https://elsewhere.com/actors/4',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1'
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'to' => 'https://elsewhere.com/actors/1',
                            'cc' => 'https://elsewhere.com/actors/3',
                            'audience' => array( 'https://elsewhere.com/actors/5' )
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
                'expectedEvent' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'to' => array(
                            'https://elsewhere.com/actors/1',
                            'https://www.w3.org/ns/activitystreams#Public',
                        ),
                        'cc' => array(
                            'https://elsewhere.com/actors/2',
                            'https://elsewhere.com/actors/3',
                        ),
                        'audience' => array(
                            'https://elsewhere.com/actors/5',
                            'https://elsewhere.com/actors/4',
                        ),
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'attributedTo' => 'https://example.com/actors/1',
                            'to' => array(
                                'https://elsewhere.com/actors/1',
                                'https://www.w3.org/ns/activitystreams#Public',
                            ),
                            'cc' => array(
                                'https://elsewhere.com/actors/2',
                                'https://elsewhere.com/actors/3',
                            ),
                            'audience' => array(
                                'https://elsewhere.com/actors/5',
                                'https://elsewhere.com/actors/4',
                            ),
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
            ) ),
            array( array(
                'id' => 'moreCopiesAudienceOutboxTest',
                'eventName' => OutboxActivityEvent::NAME,
                'event' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        'cc' => array(
                            'https://elsewhere.com/actors/2'
                        ),
                        'actor' => array(
                            'id' => 'https://example.com/actors/1'
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'cc' => array( 'https://elsewhere.com/actors/3' ),
                            'audience' => 'https://elsewhere.com/actors/5',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
                'expectedEvent' => new OutboxActivityEvent(
                    array(
                        'id' => 'https://example.com/activities/1',
                        'type' => 'Create',
                        'to' => 'https://www.w3.org/ns/activitystreams#Public',
                        'cc' => array(
                            'https://elsewhere.com/actors/2',
                            'https://elsewhere.com/actors/3',
                        ),
                        'audience' => 'https://elsewhere.com/actors/5',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://example.com/objects/1',
                            'type' => 'Note',
                            'attributedTo' => 'https://example.com/actors/1',
                            'to' => 'https://www.w3.org/ns/activitystreams#Public',
                            'cc' => array(
                                'https://elsewhere.com/actors/2',
                                'https://elsewhere.com/actors/3',
                            ),
                            'audience' => 'https://elsewhere.com/actors/5',
                        ),
                    ),
                    TestActivityPubObject::fromArray( array(
                        'id' => 'https://example.com/actors/1',
                        'type' => 'Person',
                    ) ),
                    Request::create( 'https://example.com/outbox', Request::METHOD_POST )
                ),
            ) ),
        );
    }

    /**
     * @dataProvider provideTestCreateHandler
     */
    public function testCreateHandler( $testCase )
    {
        $event = $testCase['event'];
        $this->eventDispatcher->dispatch( $testCase['eventName'], $event );
        $this->assertEquals(
            $testCase['expectedEvent'], $event, "Error on test $testCase[id]"
        );
    }
}

