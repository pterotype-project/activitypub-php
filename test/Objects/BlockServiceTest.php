<?php

namespace ActivityPub\Test\Objects;

use ActivityPub\Database\PrefixNamingStrategy;
use ActivityPub\Objects\BlockService;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\ArrayDataSet;
use ActivityPub\Test\TestConfig\SQLiteTestCase;
use ActivityPub\Test\TestUtils\TestDateTimeProvider;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Extensions_Database_DataSet_IDataSet;

class BlockServiceTest extends SQLiteTestCase
{
    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * @var BlockService
     */
    private $blockService;

    protected function setUp()
    {
        parent::setUp();
        $dbConfig = Setup::createAnnotationMetadataConfiguration(
            array( __DIR__ . '/../../src/Entities' ), true
        );
        $namingStrategy = new PrefixNamingStrategy( '' );
        $dbConfig->setNamingStrategy( $namingStrategy );
        $dbParams = array(
            'driver' => 'pdo_sqlite',
            'path' => self::getDbPath(),
        );
        $entityManager = EntityManager::create( $dbParams, $dbConfig );
        $dateTimeProvider = new TestDateTimeProvider( array(
            'objects-service.create' => new DateTime( "12:00" ),
            'objects-service.update' => new DateTime( "12:01" ),
        ) );
        $httpClient = $this->getMock( Client::class );
        $httpClient->method( 'send' )->willReturn( new Response( 404 ) );
        $this->objectsService = new ObjectsService( $entityManager, $dateTimeProvider, $httpClient );
        $this->blockService = new BlockService( $this->objectsService );
    }

    public function testGetBlockedActorIds()
    {
        $testCases = array(
            array(
                'id' => 'blocksExist',
                'initialData' => array(
                    array(
                        'id' => 'https://example.com/blocks/1',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                    ),
                ),
                'blockingActorId' => 'https://example.com/actors/1',
                'expectedBlockedActorIds' => array( 'https://elsewhere.com/actors/1' ),
            ),
            array(
                'id' => 'noBlocksExist',
                'blockingActorId' => 'https://example.com/actors/1',
                'expectedBlockedActorIds' => array(),
            ),
            array(
                'id' => 'multipleBlocksExist',
                'initialData' => array(
                    array(
                        'id' => 'https://example.com/blocks/1',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                    ),
                    array(
                        'id' => 'https://example.com/blocks/2',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/2',
                        ),
                    ),
                ),
                'blockingActorId' => 'https://example.com/actors/1',
                'expectedBlockedActorIds' => array( 'https://elsewhere.com/actors/1', 'https://elsewhere.com/actors/2' ),
            ),
            array(
                'id' => 'blocksExistsFromDifferentActor',
                'initialData' => array(
                    array(
                        'id' => 'https://example.com/blocks/1',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                    ),
                    array(
                        'id' => 'https://example.com/blocks/2',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/2',
                        ),
                    ),
                ),
                'blockingActorId' => 'https://example.com/actors/2',
                'expectedBlockedActorIds' => array(),
            ),
            array(
                'id' => 'differentTypesOfActivitiesExistWithSameObjectAndActor',
                'initialData' => array(
                    array(
                        'id' => 'https://example.com/follows/1',
                        'type' => 'Follow',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/1',
                        ),
                    ),
                    array(
                        'id' => 'https://example.com/blocks/1',
                        'type' => 'Block',
                        'actor' => array(
                            'id' => 'https://example.com/actors/1',
                        ),
                        'object' => array(
                            'id' => 'https://elsewhere.com/actors/2',
                        ),
                    ),
                ),
                'blockingActorId' => 'https://example.com/actors/1',
                'expectedBlockedActorIds' => array( 'https://elsewhere.com/actors/2' ),
            ),
        );
        foreach ( $testCases as $testCase ) {
            self::setUp();
            if ( array_key_exists( 'initialData', $testCase ) ) {
                foreach ( $testCase['initialData'] as $object ) {
                    $this->objectsService->persist( $object );
                }
            }
            $blockedActorIds = $this->blockService->getBlockedActorIds( $testCase['blockingActorId'] );
            $this->assertEquals(
                $testCase['expectedBlockedActorIds'], $blockedActorIds, "Error on test $testCase[id]"
            );
            self::tearDown();
        }
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return new ArrayDataSet( array() );
    }
}