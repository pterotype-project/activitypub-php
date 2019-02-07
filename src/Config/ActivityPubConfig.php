<?php
namespace ActivityPub\Config;

use ActivityPub\Config\ActivityPubConfigBuilder;
use ActivityPub\Objects\ContextProvider;

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
}

