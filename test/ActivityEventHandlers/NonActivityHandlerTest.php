<?php

namespace ActivityPub\Test\ActivityEventHandlers;

use ActivityPub\ActivityEventHandlers\NonActivityHandler;
use ActivityPub\ActivityEventHandlers\OutboxActivityEvent;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;
use Symfony\Component\HttpFoundation\Request;

class NonActivityHandlerTest extends APTestCase
{
    public function provideTestNonActivityHandler()
    {
        return array(
            array( array(
                'id' => 'testItWrapsNonObjectActivity',
                'activity' => array(
                    'type' => 'Note'
                ),
                'actor' => TestActivityPubObject::fromArray( array(
                    'id' => 'https://example.com/actor/1',
                ) ),
                'expectedActivity' => array(
                    '@context' => ContextProvider::getDefaultContext(),
                    'type' => 'Create',
                    'actor' => 'https://example.com/actor/1',
                    'object' => array(
                        'type' => 'Note',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'testItDoesNotWrapActivity',
                'activity' => array(
                    'type' => 'Update'
                ),
                'actor' => TestActivityPubObject::fromArray( array(
                    'id' => 'https://example.com/actor/1',
                ) ),
                'expectedActivity' => array(
                    'type' => 'Update',
                ),
            ) ),
            array( array(
                'id' => 'testItPassesAudience',
                'activity' => array(
                    'type' => 'Note',
                    'audience' => array(
                        'foo',
                    ),
                    'to' => array(
                        'bar',
                    ),
                    'bcc' => array(
                        'baz',
                    ),
                ),
                'actor' => TestActivityPubObject::fromArray( array(
                    'id' => 'https://example.com/actor/1',
                ) ),
                'expectedActivity' => array(
                    '@context' => ContextProvider::getDefaultContext(),
                    'type' => 'Create',
                    'actor' => 'https://example.com/actor/1',
                    'object' => array(
                        'type' => 'Note',
                        'audience' => array(
                            'foo',
                        ),
                        'to' => array(
                            'bar',
                        ),
                        'bcc' => array(
                            'baz',
                        ),
                    ),
                    'audience' => array(
                        'foo',
                    ),
                    'to' => array(
                        'bar',
                    ),
                    'bcc' => array(
                        'baz',
                    ),
                ),
            ) )
        );
    }

    /**
     * @dataProvider provideTestNonActivityHandler
     */
    public function testNonActivityHandler( $testCase )
    {
        $contextProvider = new ContextProvider();
        $nonActivityHandler = new NonActivityHandler( $contextProvider );
        $actor = $testCase['actor'];
        $activity = $testCase['activity'];
        $request = Request::create( 'https://example.com/whatever' );
        $event = new OutboxActivityEvent( $activity, $actor, $request );
        $nonActivityHandler->handle( $event );
        $this->assertEquals(
            $testCase['expectedActivity'],
            $event->getActivity(),
            "Error on test $testCase[id]"
        );
    }
}

