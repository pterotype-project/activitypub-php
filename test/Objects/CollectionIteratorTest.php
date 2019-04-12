<?php

namespace ActivityPub\Test\Objects;

use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Objects\CollectionIterator;
use ActivityPub\Test\ActivityPubTest;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Test\TestUtils\TestActivityPubObject;

class CollectionIteratorTest extends APTestCase
{
    public function provideTestCollectionIterator()
    {
        return array(
            array( array(
                'id' => 'basicIteration',
                'collection' => TestActivityPubObject::fromArray( array(
                    'id' => 'mycollection',
                    'type' => 'Collection',
                    'items' => array(
                        array(
                            'id' => 'item1',
                        ),
                        array(
                            'id' => 'item2',
                        ),
                        array(
                            'id' => 'item3',
                        ),
                    )
                ) ),
                'expectedItems' => array(
                    array(
                        'id' => 'item1',
                    ),
                    array(
                        'id' => 'item2',
                    ),
                    array(
                        'id' => 'item3',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'orderedCollection',
                'collection' => TestActivityPubObject::fromArray( array(
                    'id' => 'mycollection',
                    'type' => 'OrderedCollection',
                    'orderedItems' => array(
                        array(
                            'id' => 'item1',
                        ),
                        array(
                            'id' => 'item2',
                        ),
                        array(
                            'id' => 'item3',
                        ),
                    )
                ) ),
                'expectedItems' => array(
                    array(
                        'id' => 'item1',
                    ),
                    array(
                        'id' => 'item2',
                    ),
                    array(
                        'id' => 'item3',
                    ),
                ),
            ) ),
            array( array(
                'id' => 'orderedCollectionWrongItems',
                'collection' => TestActivityPubObject::fromArray( array(
                    'id' => 'mycollection',
                    'type' => 'OrderedCollection',
                    'items' => array(
                        array(
                            'id' => 'item1',
                        ),
                        array(
                            'id' => 'item2',
                        ),
                        array(
                            'id' => 'item3',
                        ),
                    )
                ) ),
                'expectedException' => \InvalidArgumentException::class,
            ) ),
            array( array(
                'id' => 'unorderedCollectionWrongItems',
                'collection' => TestActivityPubObject::fromArray( array(
                    'id' => 'mycollection',
                    'type' => 'Collection',
                    'orderedItems' => array(
                        array(
                            'id' => 'item1',
                        ),
                        array(
                            'id' => 'item2',
                        ),
                        array(
                            'id' => 'item3',
                        ),
                    )
                ) ),
                'expectedException' => \InvalidArgumentException::class,
            ) ),
        );
    }

    /**
     * @dataProvider provideTestCollectionIterator
     */
    public function testCollectionIterator( $testCase )
    {
        $this->setExpectedException( null );
        if ( array_key_exists( 'expectedException', $testCase ) ) {
            $this->setExpectedException( $testCase['expectedException'] );
        }
        foreach ( CollectionIterator::iterateCollection( $testCase['collection'] ) as $idx => $item ) {
            if ( $item instanceof ActivityPubObject ) {
                $item = $item->asArray();
            }
            $this->assertEquals( $testCase['expectedItems'][$idx], $item, "Error on test $testCase[id]" );
        }
    }
}