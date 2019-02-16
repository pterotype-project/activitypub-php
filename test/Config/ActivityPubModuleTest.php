<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Test\Config;

use ActivityPub\Config\ActivityPubConfig;
use ActivityPub\Config\ActivityPubModule;
use ActivityPub\Http\Router;
use ActivityPub\Test\TestConfig\APTestCase;
use Doctrine\ORM\EntityManager;

class ActivityPubModuleTest extends APTestCase
{
    /**
     * @var ActivityPubModule
     */
    private $module;

    public function setUp()
    {
        $config = ActivityPubConfig::createBuilder()
            ->setDbConnectionParams( array(
                'driver' => 'pdo_sqlite',
                'path' => ':memory:',
            ) )
            ->build();
        $this->module = new ActivityPubModule( $config );
    }

    public function testItInjects()
    {
        $entityManager = $this->module->get( EntityManager::class );
        $this->assertNotNull( $entityManager );
        $this->assertInstanceOf( EntityManager::class, $entityManager );

        $router = $this->module->get( Router::class );
        $this->assertNotNull( $router );
        $this->assertInstanceOf( Router::class, $router );
    }
}

