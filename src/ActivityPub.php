<?php
require_once __DIR__ . '../vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use ActivityPub\Database\PrefixNamingStrategy;

namespace ActivityPub;

class ActivityPub
{
    protected $entityManager;

    /**
     * Constructs a new ActivityPub instance
     *
     * @param array $opts Array of options. Valid keys are
     *     'dbuser', 'dbpass', 'dbname', 'dbprefix', and 'isDevMode'.
     */
    public function __construct( $opts )
    {
        $this->validateOptions( $opts );
        $defaults = array(
            'isDevMode' => false,
            'dbprefix' => '',
        );
        $options = array_merge( $defaults, $opts );
        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/src/Entities' ), $options['isDevMode']
        );
        $namingStrategy = new PrefixNamingStrategy( $options['dbprefix'] );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = array(
            'driver' => 'pdo_mysql',
            'user' => $options['dbuser'],
            'password' => $options['dbpass'],
            'dbname' => $options['dbname'],
        );
        $this->entityManager = EntityManager::create( $dbParams, $dbConfig );
        // TODO create tables
    }

    private function validateOptions( $opts )
    {
        $required = array( 'dbuser', 'dbpass', 'dbname' );
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
