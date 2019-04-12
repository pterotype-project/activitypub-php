<?php

namespace ActivityPub\Objects;

use ActivityPub\Entities\ActivityPubObject;
use InvalidArgumentException;
use Iterator;

class CollectionIterator implements Iterator
{
    /**
     * @var ActivityPubObject
     */
    private $collection;

    /**
     * @var ActivityPubObject
     */
    private $items;

    /**
     * @var int
     */
    private $idx;

    public function __construct( ActivityPubObject $collection )
    {
        if ( ! ( $collection->hasField('type') &&
            in_array( $collection['type'], array( 'Collection', 'OrderedCollection' ) ) ) ) {
            throw new InvalidArgumentException('Must pass a collection');
        }
        $itemsField = 'items';
        if ( $collection['type'] == 'OrderedCollection' ) {
            $itemsField = 'orderedItems';
        }
        $this->items = $collection[$itemsField];
        if ( ! $this->items ) {
            throw new InvalidArgumentException('Collection must have an items or orderedItems field!');
        }
        $this->collection = $collection;
        $this->idx = 0;
    }

    public static function iterateCollection( ActivityPubObject $collection )
    {
        return new CollectionIterator( $collection );
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->items[$this->idx];
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->idx += 1;
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->idx;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->items->hasField($this->idx);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->idx = 0;
    }
}