<?php
namespace ActivityPub\Config;

use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\IdProvider;
use Exception;

/**
 * The ActivityPubConfigBuilder is a builder class to create ActivityPub config data
 *
 * Usage:
 *     $config = ActivityPubConfig::createBuilder()
 *         ->setDbConnectionParams( array(
 *             'driver' => 'pdo_sqlite',
 *             'path' => __DIR__ . '/db.sqlite'
 *         ) )
 *         ->build();
 *
 * See the `set*` methods below for descriptions of available options.
 */
class ActivityPubConfigBuilder
{
    const DEFAULT_ID_PATH_PREFIX = 'ap';

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
     * Creates a new ActivityPubConfig instance with default values
     *
     * See the `set*` methods below for individual option defaults.
     */
    public function __construct()
    {
        $this->isDevMode = false;
        $this->dbPrefix = '';
        $this->authFunction = function() {
            return false;
        };
        $this->jsonLdContext = ContextProvider::getDefaultContext();
        $this->idPathPrefix = IdProvider::DEFAULT_ID_PATH_PREFIX;
    }

    /**
     * Validates and builds the config instance
     *
     * @return ActivityPubConfig
     * @throws Exception If the configuration is invalid
     */
    public function build()
    {
        $this->validate();
        return new ActivityPubConfig( $this );
    }

    /**
     * @throws Exception
     */
    private function validate()
    {
        if ( ! $this->dbConnectionParams ) {
            throw new Exception( "Missing required option 'dbConnectionParams'" );
        }
    }

    /**
     * The `dbConnectionParams` are the Doctrine connection parameters,
     * passed directly through to EntityManager::create(). See
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/tutorials/getting-started.html#obtaining-the-entitymanager
     *
     * This option is required and has no default.
     * @param array $dbConnectionParams The connection parameters
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setDbConnectionParams( array $dbConnectionParams )
    {
        $this->dbConnectionParams = $dbConnectionParams;
        return $this;
    }

    /**
     * @return array
     *
     *
     */
    public function getDbConnectionParams()
    {
        return $this->dbConnectionParams;
    }

    /**
     * If `isDevMode` is true, the Doctrine EntityManager configuration will
     * be set to development mode.
     *
     * Default: false
     * @param bool $isDevMode
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setIsDevMode( $isDevMode )
    {
        $this->isDevMode = $isDevMode;
        return $this;
    }

    /**
     * @return bool
     *
     *
     */
    public function getIsDevMode()
    {
        return $this->isDevMode;
    }

    /**
     * The `dbPrefix` is a string that is prepended to all SQL tables created
     * by the ActivityPub library. This is useful for environments like multi-site
     * WordPress installations where table prefixes are used to distinguish tables
     * for different sites.
     *
     * Default: ''
     * @param string $dbPrefix
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setDbPrefix( $dbPrefix )
    {
        $this->dbPrefix = $dbPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;
    }

    /**
     * The `authFunction` is used to bridge your application's user management
     * system with ActivityPub. It should be a Callable that takes no arguments
     * and returns the ID of the ActivityPub actor associated with the user
     * authenticated to the current request, if any. If no such actor exists,
     * it should return `false`.
     *
     * Default: function() { return false; }, i.e. HTTP signatures are the only valid
     * authentication mechanism.
     * @param Callable $authFunction
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setAuthFunction( Callable $authFunction )
    {
        $this->authFunction = $authFunction;
        return $this;
    }

    /**
     * @return Callable
     */
    public function getAuthFunction()
    {
        return $this->authFunction;
    }

    /**
     * The `jsonLdContext` option sets a custom JSON-LD context on all
     * objects created by the ActivityPub library. See https://json-ld.org/.
     *
     * Default: array( 'https://www.w3.org/ns/activitystreams',
     *                 'https://w3id.org/security/v1' )
     * @param array $jsonLdContext
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setJsonLdContext( array $jsonLdContext )
    {
        $this->jsonLdContext = $jsonLdContext;
        return $this;
    }

    /**
     * @return array
     */
    public function getJsonLdContext()
    {
        return $this->jsonLdContext;
    }

    /**
     * The `idPathPrefix` is a string that is prepended to all ids generated by
     * by the ActivityPub library. For example, if the `idPathPrefix` is 'ap',
     * the library will generate ids that look like 'https://example.com/ap/note/1'.
     *
     * Default: 'ap'
     * @param string $idPathPrefix The id path prefix
     * @return ActivityPubConfigBuilder The builder instance
     */
    public function setIdPathPrefix( $idPathPrefix )
    {
        $this->idPathPrefix = $idPathPrefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdPathPrefix()
    {
        return $this->idPathPrefix;
    }
}

