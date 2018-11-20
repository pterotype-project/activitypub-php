<?php
namespace ActivityPub\Entities;

/**
 * The index table hold the JSON-LD object graph.
 * 
 * Its structure is based on https://changelog.com/posts/graph-databases-101:
 * Every row has a subject, which is a foreign key into the Objects table,
 * a predicate, which is a the JSON field that describes the graph edge relationship
 * (e.g. 'id', 'inReplyTo', 'attributedTo'), and either a value or an object.
 * A value is a string that represents the value of the JSON-LD field if the field
 * is a static value, like { "url": "https://example.com" }. An object is another foreign
 * key into the objects table that represents the value of the JSON-LD field if the
 * field is another JSON-LD object, like { "inReplyTo": { <another object } }.
 * A subject can have multiple values for the same predicate - this represents a JSON-LD
 * array.
 */
class IndexEntity
{
    // TODO what should the primary key be?
    protected $subject;
    protected $predicate;
    protected $value;
    protected $object;
}
?>
