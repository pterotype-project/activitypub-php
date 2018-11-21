<?php
namespace ActivityPub;

require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use ActivityPub\Database\PrefixNamingStrategy;

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
