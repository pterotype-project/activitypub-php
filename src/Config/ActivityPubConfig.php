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
    }

    public function createBuilder()
    {
        return new ActivityPubConfigBuilder();
    }

    /**
     * @var array
     */
    public function getDbConnectionParams()
    {
        return $this->dbConnectionParams;
    }

    /**
     * @var bool
     */
    public function getIsDevMode()
    {
        return $this->isDevMode;
    }

    /**
     * @var string
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;

    }

    /**
     * @var Callable
     */
    public function getAuthFunction()
    {
        return $this->authFunction;
    }

    /**
     * @var array
     */
    public function getJsonLdContext()
    {
        return $this->jsonLdContext;
    }
}
?>
