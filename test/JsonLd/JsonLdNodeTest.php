<?php

namespace ActivityPub\Test\JsonLd;

use ActivityPub\JsonLd\Exceptions\PropertyNotDefinedException;
use ActivityPub\JsonLd\JsonLdNode;
use ActivityPub\JsonLd\JsonLdNodeFactory;
use ActivityPub\Test\TestConfig\APTestCase;
use stdClass;

class JsonLdNodeTest extends APTestCase
{
    private $asContext = array(
        'https://www.w3.org/ns/activitystreams',
    );

    public function provideForBasicGetProperty()
    {
        return array(
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'name',
                'Object1',
            ),
            array(
                (object) array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                    ),
                    'name' => 'Object2',
                ),
                $this->asContext,
                'name',
                'Object2',
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'https://www.w3.org/ns/activitystreams#name',
                'Object1',
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'foo',
                null,
                PropertyNotDefinedException::class,
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                (object) array(
                    'as' => 'https://www.w3.org/ns/activitystreams#'
                ),
                'as:name',
                'Object1',
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#subject' => array(
                        'https://example.org/item/1',
                        'https://example.org/item/2',
                    ),
                ),
                $this->asContext,
                'subject',
                'https://example.org/item/1',
            ),
        );
    }

    /**
     * @dataProvider provideForBasicGetProperty
     */
    public function testBasicGetProperty( $inputObj, $context, $propertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $propertyValue = $node->get( $propertyName );
        $this->assertEquals( $expectedValue, $propertyValue );
    }

    /**
     * @dataProvider provideForBasicGetProperty
     */
    public function testBasicMagicGetProperty( $inputObj, $context, $propertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $propertyValue = $node->$propertyName;
        $this->assertEquals( $expectedValue, $propertyValue );
    }

    /**
     * @dataProvider provideForBasicGetProperty
     */
    public function testBasicArrayAccessProperty( $inputObj, $context, $propertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $propertyValue = $node[$propertyName];
        $this->assertEquals( $expectedValue, $propertyValue );
    }

    public function provideForBasicGetMany()
    {
        return array(
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'name',
                array( 'Object1' ),
            ),
            array(
                (object) array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                    ),
                    'name' => 'Object2',
                ),
                $this->asContext,
                'name',
                array( 'Object2' ),
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'https://www.w3.org/ns/activitystreams#name',
                array( 'Object1' ),
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                $this->asContext,
                'foo',
                null,
                PropertyNotDefinedException::class,
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'Object1',
                ),
                (object) array(
                    'as' => 'https://www.w3.org/ns/activitystreams#'
                ),
                'as:name',
                array( 'Object1' ),
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#subject' => array(
                        'https://example.org/item/1',
                        'https://example.org/item/2',
                    ),
                ),
                $this->asContext,
                'subject',
                array( 'https://example.org/item/1', 'https://example.org/item/2' ),
            ),
        );
    }

    /**
     * @dataProvider provideForBasicGetMany
     */
    public function testBasicGetMany( $inputObj, $context, $propertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $propertyValue = $node->getMany( $propertyName );
        $this->assertEquals( $expectedValue, $propertyValue );
    }

    public function provideForBasicSetProperty()
    {
        return array(
            array(
                new stdClass(),
                $this->asContext,
                'name',
                'NewName',
                'https://www.w3.org/ns/activitystreams#name',
                'NewName'
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'OldName',
                ),
                $this->asContext,
                'name',
                'NewName',
                'https://www.w3.org/ns/activitystreams#name',
                'NewName'
            ),
        );
    }

    /**
     * @dataProvider provideForBasicSetProperty
     */
    public function testBasicSetProperty( $inputObj, $context, $propertyName, $newValue, $getPropertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $node->set( $propertyName, $newValue );
        $this->assertEquals( $expectedValue, $node->$getPropertyName );
    }

    /**
     * @dataProvider provideForBasicSetProperty
     */
    public function testBasicMagicSetProperty( $inputObj, $context, $propertyName, $newValue, $getPropertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $node->$propertyName = $newValue;
        $this->assertEquals( $expectedValue, $node->$getPropertyName );
    }

    /**
     * @dataProvider provideForBasicSetProperty
     */
    public function testBasicArraySetProperty( $inputObj, $context, $propertyName, $newValue, $getPropertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $node[$propertyName] = $newValue;
        $this->assertEquals( $expectedValue, $node[$getPropertyName] );
    }

    public function provideForBasicAddPropertyValue()
    {
        return array(
            array(
                new stdClass(),
                $this->asContext,
                'name',
                'NewName',
                'name',
                array( 'NewName' ),
            ),
            array(
                (object) array(
                    'https://www.w3.org/ns/activitystreams#name' => 'OldName',
                ),
                $this->asContext,
                'name',
                'NewName',
                'name',
                array( 'OldName', 'NewName' ),
            ),
        );
    }

    /**
     * @dataProvider provideForBasicAddPropertyValue
     */
    public function testBasicAddPropertyValue( $inputObj, $context, $propertyName, $newValue, $getPropertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $node->add( $propertyName, $newValue );
        $this->assertEquals( $expectedValue, $node->getMany( $getPropertyName ) );
    }

    public function provideForGetLinkedNode()
    {
        return array(
            array(
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'type' => 'Announce',
                    'object' => 'https://example.org/objects/1',
                ),
                $this->asContext,
                array(
                    'https://example.org/objects/1' => (object) array(
                        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                        'id' => 'https://example.org/objects/1',
                        'type' => 'Note',
                    ),
                ),
                'object',
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'id' => 'https://example.org/objects/1',
                    'type' => 'Note',
                ),
            ),
        );
    }

    /**
     * @dataProvider provideForGetLinkedNode
     */
    public function testGetLinkedNode( $inputObj, $context, $nodeGraph, $propertyName, $expectedValue, $expectedException = null )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context, $nodeGraph );
        if ( $expectedException ) {
            $this->setExpectedException( $expectedException );
        }
        $actualValue = $node->get( $propertyName );
        if ( $actualValue instanceof JsonLdNode ) {
            $actualValue = $actualValue->asObject();
        }
        $this->assertEquals( $expectedValue, $actualValue );
    }

    private function makeJsonLdNode( $inputObj, $context, $nodeGraph = array() )
    {
        $factory = new JsonLdNodeFactory( $context, new TestDereferencer( $nodeGraph ) );
        return $factory->newNode( $inputObj );
    }
}