<?php
namespace ActivityPub\Config;

use ActivityPub\Auth\AuthListener;
use ActivityPub\Auth\AuthService;
use ActivityPub\Auth\SignatureListener;
use ActivityPub\Controllers\GetController;
use ActivityPub\Controllers\PostController;
use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Http\Router;
use ActivityPub\Objects\ContextProvider;
use ActivityPub\Objects\CollectionsService;
use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\RandomProvider;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The ActivityPubModule is responsible for setting up all the services for the library
 */
class ActivityPubModule
{
    const COLLECTION_PAGE_SIZE = 20;

    /**
     * @var ContainerBuilder
     */
    private $injector;

    public function __construct( $options )
    {
        $defaults = array(
            'isDevMode' => false,
            'dbPrefix' => '',
            'authFunction' => function() {
                return false;
            },
            'context' => array(
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ),
        );
        $options = array_merge( $defaults, $options );
        $this->validateOptions( $options );

        $this->injector = new ContainerBuilder;

        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/../Entities' ), $options['isDevMode']
        );
        $namingStrategy = new PrefixNamingStrategy( $options['dbPrefix'] );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = $options['dbOptions'];
        $this->injector->register( EntityManager::class, EntityManager::class )
            ->setArguments( array( $dbParams, $dbConfig ) )
            ->setFactory( array( EntityManager::class, 'create' ) );

        $this->injector->register( Client::class, Client::class )
            ->addArgument( array( 'http_errors' => false ) );

        $this->injector->register( EventDispatcher::class, EventDispatcher::class );

        $this->injector->register(
            SimpleDateTimeProvider::class, SimpleDateTimeProvider::class
        );

        $this->injector->register( ObjectsService::class, ObjectsService::class )
            ->addArgument( new Reference( EntityManager::class ) )
            ->addArgument( new Reference( SimpleDateTimeProvider::class ) )
            ->addArgument( new Reference( Client::class ) );

        $this->injector->register(
            HttpSignatureService::class, HttpSignatureService::class
        )->addArgument( new Reference( SimpleDateTimeProvider::class ) );

        $this->injector->register( SignatureListener::class, SignatureListener::class )
            ->addArgument( new Reference( HttpSignatureService::class ) )
            ->addArgument( new Reference( ObjectsService::class ) );

        $this->injector->register( AuthListener::class, AuthListener::class )
            ->addArgument( $options['authFunction'] );

        $this->injector->register( AuthService::class, AuthService::class );

        $this->injector->register( ContextProvider::class, ContextProvider::class )
            ->addArgument( $options['context'] );

        $this->injector->register( CollectionsService::class, CollectionsService::class )
            ->addArgument( self::COLLECTION_PAGE_SIZE )
            ->addArgument( new Reference( AuthService::class ) )
            ->addArgument( new Reference( ContextProvider::class ) );

        $this->injector->register( RandomProvider::class, RandomProvider::class );

        $this->injector->register( IdProvider::class, IdProvider::class )
            ->addArgument( new Reference( ObjectsService::class ) )
            ->addArgument( new Reference( RandomProvider::class ) );

        $this->injector->register( GetController::class, GetController::class )
            ->addArgument( new Reference( ObjectsService::class ) )
            ->addArgument( new Reference( CollectionsService::class ) )
            ->addArgument( new Reference( AuthService::class ) );

        $this->injector->register( PostController::class, PostController::class )
            ->addArgument( new Reference( EventDispatcher::class ) )
            ->addArgument( new Reference( ObjectsService::class ) );

        $this->injector->register( Router::class, Router::class )
            ->addArgument( new Reference( GetController::class ) )
            ->addArgument( new Reference( PostController::class ) );
    }

    /**
     * Returns the service identified by $id
     *
     * @param string $id The id of the service instance to get
     * @return object The service instance
     */
    public function get( string $id )
    {
        return $this->injector->get( $id );
    }

    private function validateOptions( $options )
    {
        $required = array( 'dbOptions' );
        $actual = array_keys( $options );
        $missing = array_diff( $required, $actual );
        if ( count( $missing ) > 0 ) {
            throw new InvalidArgumentException(
                'Missing required options: ' . print_r( $missing, t )
            );
        }
    }

}
?>
