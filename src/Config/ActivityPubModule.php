<?php
namespace ActivityPub\Config;

use ActivityPub\Auth\AuthListener;
use ActivityPub\Auth\SignatureListener;
use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Database\PrefixNamingStrategy;
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

        $this->injector->register( 'entityManager', EntityManager::class )
            ->setArguments( array( $dbParams, $dbConfig ) )
            ->setFactory( array( EntityManager::class, 'create' ) );

        $this->injector->register( 'httpClient', Client::class )
            ->addArgument( array( 'http_errors' => false ) );

        $this->injector->register( 'dateTimeProvider', SimpleDateTimeProvider::class );

        $this->injector->register( 'objectsService', ObjectsService::class )
            ->addArgument( new Reference( 'entityManager' ) )
            ->addArgument( new Reference( 'dateTimeProvider' ) )
            ->addArgument( new Reference( 'httpClient' ) );

        $this->injector->register( 'httpSignatureService', HttpSignatureService::class )
            ->addArgument( new Reference( 'dateTimeProvider' ) );

        $this->injector->register( 'signatureListener', SignatureListener::class )
            ->addArgument( new Reference( 'httpSignatureService' ) )
            ->addArgument( new Reference( 'objectsService' ) );

        $this->injector->register( 'authListener', AuthListener::class )
            ->addArgument( $options['authFunction'] );
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
