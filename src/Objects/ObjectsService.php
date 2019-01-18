<?php
namespace ActivityPub\Objects;

use DateTime;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Utils\Util;
use ActivityPub\Utils\DateTimeProvider;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ObjectsService
{
    /** 
     * @var EntityManager 
     */
    protected $entityManager;
    /** 
     * @var DateTimeProvider 
     */
    protected $dateTimeProvider;
    /** 
     * @var Client 
     */
    protected $httpClient;

    public function __construct( EntityManager $entityManager,
                                 DateTimeProvider $dateTimeProvider,
                                 Client $client)
    {
        $this->entityManager = $entityManager;
        $this->dateTimeProvider = $dateTimeProvider;
        $this->httpClient = $client;
    }

    /**
     * Persists a new object to the database with fields defined by $fields
     *
     * If there is an 'id' field, and an object with that id already exists,
     *   this returns the existing object rather than persisting the new object.
     *   The existing object will not have its fields modified.
     *
     * @param array $fields The fields that define the new object
     * @param string $context The context to retrieve the current time in. 
     *   Used for fixing the time in tests.
     *
     * @return ActivityPubObject The created object
     */
    public function persist( array $fields, string $context = 'create' )
    {
        // TODO should I do JSON-LD compaction here?
        if ( array_key_exists( 'id', $fields ) ) {
            $existing = $this->getObject( $fields['id'] );
            if ( $existing ) {
                return $existing;
            }
        }
        $object = new ActivityPubObject( $this->dateTimeProvider->getTime( $context ) );
        $this->entityManager->persist( $object );
        foreach ( $fields as $name => $value ) {
            $this->persistField( $object, $name, $value, $context );
        }
        $this->entityManager->flush();
        return $object;
    }

    /**
     * Persists a field. 
     *
     * If the field is an array, persist it as a child object
     *
     * @param ActivityPubObject $object
     * @param string $fieldName
     * @param string|array $fieldValue
     *
     */
    private function persistField( $object, $fieldName, $fieldValue, $context = 'create' )
    {
        if ( is_array( $fieldValue ) ) {
            $referencedObject = $this->persist( $fieldValue, $context );
            $fieldEntity = Field::withObject(
                $object, $fieldName, $referencedObject, $this->dateTimeProvider->getTime( $context )
            );
            $this->entityManager->persist( $fieldEntity );
        } else {
            if ( $fieldName !== 'id' &&
                 filter_var( $fieldValue, FILTER_VALIDATE_URL ) !== false ) {
                $dereferenced = $this->dereference( $fieldValue );
                if ( $dereferenced ) {
                    $fieldEntity = Field::withObject(
                        $object, $fieldName, $dereferenced, $this->dateTimeProvider->getTime( $context )
                    );
                    $this->entityManager->persist( $fieldEntity );
                    return;
                }
            }
            $fieldEntity = Field::withValue(
                $object, $fieldName, $fieldValue, $this->dateTimeProvider->getTime( $context )
            );
            $this->entityManager->persist( $fieldEntity);
        }
    }

    /**
     * Returns the full object represented by $id, expanded up to depth $depth
     *
     * This method will first check the local DB for the object. If it's not there,
     * it will request the object from that object's server, fully expand it by
     * dereferencing any children that are id values, and persist it to the local DB
     * before returning the object collapsed to $depth.
     *
     * @param string $id The id of the object to dereference
     *
     * @return ActivityPubObject|null The dereferenced object if it exists
     */
    public function dereference( $id )
    {
        $object = $this->getObject( $id );
        if ( $object ) {
            return $object;
        }
        // TODO sign this request?
        $request = new Request( 'GET', $id, array(
            'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
        ) );
        $response = $this->httpClient->send( $request );
        if ( $response->getStatusCode() !== 200 || empty( $response->getBody() ) ) {
            return;
        }
        $object = json_decode( $response->getBody() );
        if ( ! $object ) {
            return;
        }
        return $this->persist( $object );
    }

    /**
     * Queries for an object with certain field values
     *
     * @param array $queryTerms An associative array where the keys are field 
     *   names and the values are the values to query for. The value for a key
     *   can also be another associative array, which represents a field
     *   containing a target object that matches the given nested query.
     *   Finally, the value could be a sequential array, which represents a field
     *   containing all of the specified values (the field could also contain more
     *   values).
     *
     * @return ActivityPubObject[] The objects that match the query, if any,
     *   ordered by created timestamp from newest to oldest
     */
    public function query( $queryTerms )
    {
        $qb = $this->getObjectQuery( $queryTerms );
        $query = $qb->getQuery();
        return $query->getResult();
    }

    /**
     * Generates the Doctrine QueryBuilder that represents the query
     *
     * This function is recursive; it traverses the query tree to build up the 
     *   final expression
     *
     * @param array $queryTerms The query terms from which to generate the expressions
     * @param int $nonce A nonce value to differentiate field names
     * @return QueryBuilder The expression
     */
    protected function getObjectQuery( $queryTerms, $nonce = 0 )
    {
        $qb = $this->entityManager->createQueryBuilder();
        $exprs = array();
        foreach( $queryTerms as $fieldName => $fieldValue ) {
            if ( is_array( $fieldValue ) ) {
                $subQuery = $this->getObjectQuery( $fieldValue, $nonce + 1 );
                $exprs[] = $qb->expr()->andX(
                    $qb->expr()->like(
                        "field$nonce.name",
                        $qb->expr()->literal( (string) $fieldName )
                    ),
                    $qb->expr()->in( "field$nonce.targetObject", $subQuery->getDql())
                );
            } else {
                $exprs[] = $qb->expr()->andX(
                    $qb->expr()->like(
                        "field$nonce.name",
                        $qb->expr()->literal( (string) $fieldName )
                    ),
                    $qb->expr()->like(
                        "field$nonce.value",
                        $qb->expr()->literal( $fieldValue )
                    )
                );
            }
        }
        return $qb->select( "object$nonce" )
            ->from( 'ActivityPub\Entities\ActivityPubObject', "object$nonce" )
            ->join( "object{$nonce}.fields", "field$nonce" )
            ->where( call_user_func_array(
                array( $qb->expr(), 'orX' ),
                $exprs
            ) )
            ->groupBy( "object$nonce" )
            ->having( $qb->expr()->eq(
                $qb->expr()->count( "field$nonce" ),
                count( $queryTerms )
            ) );
    }

    /**
     * Gets an object from the DB by its ActivityPub id
     *
     * For internal use only - external callers should use dereference()
     *
     * @param string $id The object's id
     * @return ActivityPubObject|null The object or null
     *   if no object exists with that id
     */
    protected function getObject( $id )
    {
        $results = $this->query( array( 'id' => $id ) );
        if ( ! empty( $results ) ) {
            return $results[0];
        }
    }

    /**
     * Updates $object
     *
     * @param string $id The ActivityPub id of the object to update
     * @param array $updatedFields An array where the key is a field name
     *   to update and the value is the field's new value. If the value is
     *   null, the field will be deleted.
     *
     * If the update results in an orphaned anonymous node (an ActivityPubObject
     *   with no 'id' field that no longer has any references to it), then the
     *   orphaned node will be deleted.
     *
     * @return ActivityPubObject|null The updated object,
     *   or null if an object with that id isn't in the DB
     */
    public function update( $id, $updatedFields )
    {
        $object = $this->getObject( $id );
        if ( ! $object ) {
            return;
        }
        foreach( $object->getFields() as $field ) {
            if ( array_key_exists( $field->getName(), $updatedFields ) ) {
                $newValue = $updatedFields[$field->getName()];
                if ( is_array( $newValue ) ) {
                    $referencedObject = $this->persist( $newValue, 'update' );
                    $oldTargetObject = $field->getTargetObject();
                    $field->setTargetObject(
                        $referencedObject, $this->dateTimeProvider->getTime( 'update' )
                    );
                    $this->entityManager->persist( $field );
                    if ( $oldTargetObject && ! $oldTargetObject->hasField( 'id' ) ) {
                        $this->entityManager->remove( $oldTargetObject );
                    }
                } else if ( ! $newValue ) {
                    $object->removeField( $field );
                    $this->entityManager->persist( $object );
                    $this->entityManager->remove( $field );
                } else {
                    $field->setValue( $newValue, $this->dateTimeProvider->getTime( 'update' ) );
                    $this->entityManager->persist( $field );
                }
            }
        }
        $object->setLastUpdated( $this->dateTimeProvider->getTime( 'update' ) );
        $this->entityManager->persist( $object );
        $this->entityManager->flush();
        return $object;
    }
}
?>
