<?php

namespace ActivityPub\Entities;

/**
 * The keys table holds the private keys associated with ActivityPub actors
 *
 * @Entity @Table(name="keys")
 */
class PrivateKey
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * The private key
     *
     * @var string
     * @Column(type="string")
     */
    protected $key;

    /**
     * The object associated with this private key
     *
     * @var ActivityPubObject
     * @OneToOne(targetEntity="ActivityPubObject", inversedBy="privateKey")
     */
    protected $object;

    /**
     * Creates a new PrivateKey
     *
     * Don't call this directly - instead, use ActivityPubObject->setPrivateKey()
     * @param string $key The private key as a string
     * @param ActivityPubObject $object The object associated with this key
     */
    public function __construct( $key, ActivityPubObject $object )
    {
        $this->key = $key;
        $this->object = $object;
    }

    /**
     * Sets the private key string
     *
     * Don't call this directly - instead, use ActivityPubObject->setPrivateKey()
     * @param string $key The private key as a string
     */
    public function setKey( $key )
    {
        $this->key = $key;
    }
}

