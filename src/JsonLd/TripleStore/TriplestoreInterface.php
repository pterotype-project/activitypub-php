<?php


namespace ActivityPub\JsonLd\TripleStore;


/**
 * A triplestore is a type of graph database that stores RDF triples. This interface defines the TripleStore API
 * that ActivityPub-PHP relies on to persist JSON-LD nodes.
 * Interface TripleStoreInterface
 * @package ActivityPub\JsonLd\TripleStore
 */
interface TriplestoreInterface
{
    /**
     * Persists a triple. If the specified triple already exists in the store, this is a no-op.
     * The triple must be fully specified, i.e. none of the subject, the predicate, or the object can be null.
     * @param TypedRdfTriple $triple
     */
    public function storeTriple( TypedRdfTriple $triple );

    /**
     * Persists multiple triples. If any of the specified triples already exist in the store, this is a no-op for those triples.
     * All the triples must be fully specified, i.e. none of the subject, the predicate, or the object can be null.
     * @param TypedRdfTriple[] $triples
     */
    public function storeTriples( $triples );

    /**
     * Deletes a triple. If the specified triple doesn't exist, this is a no-op.
     * The triple must be fully specified, i.e. none of the subject, the predicate, or the object can be null.
     * @param TypedRdfTriple $triple
     */
    public function deleteTriple( TypedRdfTriple $triple );

    /**
     * Deletes multiple triples. If any of the specified triples don't exist, this is a no-op for those triples.
     * All the triples must be fully specified, i.e. none of the subject, the predicate, or the object can be null.
     * @param TypedRdfTriple[] $triples
     */
    public function deleteTriples( $triples );

    /**
     * Selects triples that match the selection from the store.
     *
     * The selection is a triple where any of the subject, predicate, or object can be null. This method will
     * return the set of triples that match the terms that are specified in the selection. For example, given
     * the selection ('https://example.com', null, null), this method will return the set of all triples whose
     * subject is 'https://example.com', with any predicate and any object. The triple (null, null, null) will select
     * the set of all triples.
     * @param TypedRdfTriple $selection
     * @return TypedRdfTriple[]
     */
    public function select( TypedRdfTriple $selection );
}