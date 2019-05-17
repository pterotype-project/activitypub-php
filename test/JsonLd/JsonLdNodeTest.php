<?php

namespace ActivityPub\Test\JsonLd;

use ActivityPub\JsonLd\Exceptions\NodeNotFoundException;
use ActivityPub\JsonLd\Exceptions\PropertyNotDefinedException;
use ActivityPub\JsonLd\JsonLdNode;
use ActivityPub\JsonLd\JsonLdNodeFactory;
use ActivityPub\JsonLd\TripleStore\TypedRdfTriple;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestUuidProvider;
use ActivityPub\Utils\Logger;
use stdClass;

class JsonLdNodeTest extends APTestCase
{
    private $uuids = array(
        'ae699da1-2d11-4b60-91f9-e3e594fa0df9',
        '5fb2dd08-be6f-4008-be9e-879ce072d308',
        '5390f3ff-9ec4-40f2-9583-5d03a4782016',
        'e560ea21-6d95-4dec-8646-6b3180544287',
        'ee8a5dc0-e53b-4397-9f38-6f70551a4a2d',
        '7e74832d-1ff7-46e7-94ad-77e0e69f6c8a',
        'ff46dbe9-99f2-4378-bec1-99b9ceb09d2f',
        '27e81733-e587-4b8e-9991-305e56df426e',
        'aedfa3dc-cc55-45ca-ad37-156a3c45b7bb',
        '8cd764f7-4463-463a-8271-697a42b6e7a7',
    );

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
            array(
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'type' => 'Announce',
                    'object' => 'https://example.org/objects/1',
                ),
                $this->asContext,
                array(),
                'object',
                null,
                NodeNotFoundException::class,
            ),
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
                        'inReplyTo' => (object) array(
                            'id' => 'https://example.org/articles/1',
                            'type' => 'Article',
                        ),
                    ),
                ),
                'object',
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'id' => 'https://example.org/objects/1',
                    'type' => 'Note',
                    'inReplyTo' => (object) array(
                        'id' => 'https://example.org/articles/1',
                        'type' => 'Article',
                    ),
                ),
            ),
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
                        'inReplyTo' => 'https://example.org/articles/1',
                    ),
                ),
                'object',
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'id' => 'https://example.org/objects/1',
                    'type' => 'Note',
                    'inReplyTo' => 'https://example.org/articles/1',
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

    public function provideForBackreferencesOnGet()
    {
        return array(
            array(
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'id' => 'https://example.org/objects/1',
                    'type' => 'Note',
                    'inReplyTo' => (object) array(
                        'id' => 'https://example.org/articles/1',
                        'type' => 'Article',
                    ),
                ),
                $this->asContext,
                'inReplyTo',
            ),
        );
    }

    /**
     * @dataProvider provideForBackreferencesOnGet
     */
    public function testBackreferencesOnGet( $inputObj, $context, $childNodeField, $nodeGraph = array() )
    {
        $parentNode = $this->makeJsonLdNode( $inputObj, $context, $nodeGraph );
        $childNode = $parentNode->$childNodeField;
        $this->assertInstanceOf( JsonLdNode::class, $childNode );
        $this->assertEquals( $childNode->getBackReferences( $childNodeField ), array( $parentNode ) );
    }

    public function provideForBackreferencesOnSet()
    {
        return array(
            array(
                new stdClass(),
                $this->asContext,
                'inReplyTo',
                (object) array(
                    'id' => 'https://example.org/articles/1',
                    'type' => 'Article',
                )
            ),
            array(
                new stdClass(),
                $this->asContext,
                'to',
                array(
                    (object) array(
                        'id' => 'https://example.org/sally',
                        'type' => 'Person',
                    ),
                    (object) array(
                        'id' => 'https://example.org/bob',
                        'type' => 'Person',
                    )
                ),
            )
        );
    }

    /**
     * @dataProvider provideForBackreferencesOnSet
     */
    public function testBackreferencesOnSet( $inputObj, $context, $newPropertyName, $newNodeValue )
    {
        $parentNode = $this->makeJsonLdNode( $inputObj, $context );
        $parentNode->set( $newPropertyName, $newNodeValue );
        $childNodes = $parentNode->getMany( $newPropertyName );
        foreach ( $childNodes as $childNode ) {
            $this->assertInstanceOf( JsonLdNode::class, $childNode );
            $this->assertEquals( $childNode->getBackReferences( $newPropertyName ), array( $parentNode ) );
        }
    }

    public function provideToRdfTriple()
    {
        return array(
            array(
                (object) array(
                    '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                    'id' => 'https://example.org/collections/1',
                    'type' => 'Collection',
                    'name' => 'MyCollection',
                    'published' => '2019-05-01',
                    'items' => array(
                        'https://example.org/collections/1/items/1',
                        'https://example.org/collections/1/items/2',
                    )
                ),
                $this->asContext,
                array(
                    TypedRdfTriple::create(
                        'https://example.org/collections/1',
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'https://www.w3.org/ns/activitystreams#Collection',
                        '@id'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1',
                        'https://www.w3.org/ns/activitystreams#items',
                        'https://example.org/collections/1/items/1',
                        '@id'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1',
                        'https://www.w3.org/ns/activitystreams#items',
                        'https://example.org/collections/1/items/2',
                        '@id'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1',
                        'https://www.w3.org/ns/activitystreams#name',
                        'MyCollection',
                        'http://www.w3.org/2001/XMLSchema#string'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1',
                        'https://www.w3.org/ns/activitystreams#published',
                        '2019-05-01',
                        'http://www.w3.org/2001/XMLSchema#dateTime'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1/items/1',
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'https://www.w3.org/ns/activitystreams#Note',
                        '@id'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/collections/1/items/2',
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'https://www.w3.org/ns/activitystreams#Note',
                        '@id'
                    ),
                ),
                array(
                    'https://example.org/collections/1/items/1' => (object) array(
                        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                        'id' => 'https://example.org/collections/1/items/1',
                        'type' => 'Note',
                    ),
                    'https://example.org/collections/1/items/2' => (object) array(
                        '@context' => array( 'https://www.w3.org/ns/activitystreams' ),
                        'id' => 'https://example.org/collections/1/items/2',
                        'type' => 'Note',
                    ),
                ),
            ),
            array(
                (object) array(
                    '@context' => array(
                        'https://www.w3.org/ns/activitystreams',
                        'https://w3id.org/security/v1',
                    ),
                    'id' => 'https://example.org/sally',
                    'type' => 'Actor',
                    'publicKey' => (object) array(
                        'publicKeyPem' => 'the_public_key',
                    )
                ),
                $this->asContext,
                array(
                    TypedRdfTriple::create(
                        'https://example.org/sally',
                        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                        'https://www.w3.org/ns/activitystreams#Actor',
                        '@id'
                    ),
                    TypedRdfTriple::create(
                        'https://example.org/sally',
                        'https://w3id.org/security/v1#publicKey',
                        $this->uuids[0]
                    ),
                    TypedRdfTriple::create(
                        $this->uuids[0],
                        'https://w3id.org/security/v1#publicKeyPem',
                        'the_public_key',
                        '@id'
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider provideToRdfTriple
     */
    public function testToRdfTriple( $inputObj, $context, $expectedTriples, $nodeGraph = array() )
    {
        $node = $this->makeJsonLdNode( $inputObj, $context, $nodeGraph );
        $triples = $node->toRdfTriples();
        $this->assertEquals( $expectedTriples, $triples );
    }

    private function makeJsonLdNode( $inputObj, $context, $nodeGraph = array() )
    {
        $factory = new JsonLdNodeFactory(
            $context, new TestDereferencer( $nodeGraph ), new Logger(), new TestUuidProvider( $this->uuids )
        );
        return $factory->newNode( $inputObj );
    }
}