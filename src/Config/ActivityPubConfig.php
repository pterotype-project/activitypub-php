<?php

namespace ActivityPub\Config;

/**
 * The ActivityPubConfig is a data class to hold ActivityPub configuration options
 */
class ActivityPubConfig
{
    /**
     * @var array
     */
    private $dbConnectionParams;

    /**
     * @var bool
     */
    private $isDevMode;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var Callable
     */
    private $authFunction;

    /**
     * @var array
     */
    private $jsonLdContext;

    /**
     * @var string
     */
    private $idPathPrefix;

    /**
     * @var bool
     */
    private $autoAcceptsFollows;

    /**
     * Don't call this directly - instead, use
     * ActivityPubConfig->createBuilder()->build()
     *
     * @param ActivityPubConfigBuilder $builder
     */
    public function __construct( ActivityPubConfigBuilder $builder )
    {
        $this->dbConnectionParams = $builder->getDbConnectionParams();
        $this->isDevMode = $builder->getIsDevMode();
        $this->dbPrefix = $builder->getDbPrefix();
        $this->authFunction = $builder->getAuthFunction();
        $this->jsonLdContext = $builder->getJsonLdContext();
        $this->idPathPrefix = $builder->getIdPathPrefix();
        $this->autoAcceptsFollows = $builder->getAutoAcceptsFollows();
    }

    public static function createBuilder()
    {
        return new ActivityPubConfigBuilder();
    }

    /**
     * @return array
     */
    public function getDbConnectionParams()
    {
        return $this->dbConnectionParams;
    }

    /**
     * @return bool
     */
    public function getIsDevMode()
    {
        return $this->isDevMode;
    }

    /**
     * @return string
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;

    }

    /**
     * @return Callable
     */
    public function getAuthFunction()
    {
        return $this->authFunction;
    }

    /**
     * @return array
     */
    public function getJsonLdContext()
    {
        return $this->jsonLdContext;
    }

    /**
     * @return string
     */
    public function getIdPathPrefix()
    {
        return $this->idPathPrefix;
    }

    /**
     * @return bool
     */
    public function getAutoAcceptsFollows()
    {
        return $this->autoAcceptsFollows;
    }
}

