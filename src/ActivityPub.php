<?php
namespace ActivityPub;

require_once __DIR__ . '/../vendor/autoload.php';

use ActivityPub\Database\PrefixNamingStrategy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class ActivityPub
{
    protected $entityManager;

    /**
     * Constructs a new ActivityPub instance
     *
     * @param array $opts Array of options. Valid keys are
     *     'dbOptions', 'dbprefix', and 'isDevMode'.
     */
    public function __construct( array $opts )
    {
        $defaults = array(
            'isDevMode' => false,
            'dbPrefix' => '',
        );
        $options = array_merge( $defaults, $opts );
        $this->validateOptions( $options );
        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/Entities' ), $options['isDevMode']
        );
        $namingStrategy = new PrefixNamingStrategy( $options['dbPrefix'] );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = $options['dbOptions'];
        $this->entityManager = EntityManager::create( $dbParams, $dbConfig );
    }

    /**
     * Handles an incoming ActivityPub request
     *
     * @param Request $request (optional) The Symfony request object.
     *   If not passed in, it is generated from the request globals.
     *
     * @return Response The response. Can be sent to the client with $response->send().
     */
    public function handle( $request = null )
    {
        if ( ! $request ) {
            $request = Request::createFromGlobals();
        }

        $dispatcher = new EventDispatcher();
        // TODO add listeners here

        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();

        $kernel = new HttpKernel(
            $dispatcher, $controllerResolver, new RequestStack(), $argumentResolver
        );
        return $kernel->handle( $request );
    }

    public function updateSchema()
    {
        $schemaTool = new SchemaTool( $this->entityManager );
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema( $classes );
    }

    private function validateOptions( $opts )
    {
        $required = array( 'dbOptions' );
        $actual = array_keys( $opts );
        $missing = array_diff( $required, $actual );
        if ( count( $missing ) > 0 ) {
            throw new InvalidArgumentException(
                'Missing required options: ' . print_r( $missing, t )
            );
        }
    }
}
?>
