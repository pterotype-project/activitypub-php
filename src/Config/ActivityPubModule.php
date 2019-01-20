<?php
namespace ActivityPub\Config;

use ActivityPub\Auth\AuthListener;
use ActivityPub\Auth\SignatureListener;
use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Controllers\InboxController;
use ActivityPub\Controllers\OutboxController;
use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Http\ControllerResolver;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\SimpleDateTimeProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The ActivityPubModule is responsible for setting up all the services for the library
 */
class ActivityPubModule
{
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

        $this->injector->register( GetObjectController::class, GetObjectController::class )
            ->addArgument( new Reference( ObjectsService::class ) );

        $this->injector->register( InboxController::class, InboxController::class )
            ->addArgument( new Reference( ObjectsService::class ) );

        $this->injector->register( OutboxController::class, OutboxController::class )
            ->addArgument( new Reference( ObjectsService::class ) );

        $this->injector->register( ControllerResolver::class, ControllerResolver::class )
            ->addArgument( new Reference( ObjectsService::class ) )
            ->addArgument( new Reference( GetObjectController::class ) )
            ->addArgument( new Reference( InboxController::class ) )
            ->addArgument( new Reference( OutboxController::class ) );
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
