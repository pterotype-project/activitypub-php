<?php

namespace ActivityPub\Test\Objects;

use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Test\TestConfig\APTestCase;
use ActivityPub\Utils\RandomProvider;
use Symfony\Component\HttpFoundation\Request;

class IdProviderTest extends APTestCase
{
    const EXISTING_ID_STR = 'exists';

    private $objectsService;

    public function setUp()
    {
        $this->objectsService = $this->getMock( ObjectsService::class );
        $this->objectsService->method( 'query' )
            ->will( $this->returnCallback( function ( $query ) {
                $existsId = sprintf(
                    'https://example.com/ap/objects/%s', self::EXISTING_ID_STR
                );
                if ( array_key_exists( 'id', $query ) && $query['id'] == $existsId ) {
                    return array( 'existingObject' );
                } else {
                    return array();
                }
            } ) );
    }

    public function testIdProvider()
    {
        $testCases = array(
            array(
                'id' => 'providesId',
                'providedRnd' => array( 'foo' ),
                'expectedId' => 'https://example.com/ap/objects/foo',
            ),
            array(
                'id' => 'checksForExisting',
                'providedRnd' => array( self::EXISTING_ID_STR, 'bar' ),
                'expectedId' => 'https://example.com/ap/objects/bar',
            ),
            array(
                'id' => 'addsPath',
                'providedRnd' => array( 'foo' ),
                'path' => 'notes',
                'expectedId' => 'https://example.com/ap/notes/foo',
            ),
        );
        foreach ( $testCases as $testCase ) {
            $randomProvider = $this->getMock( RandomProvider::class );
            call_user_func_array(
                array( $randomProvider->method( 'randomString' ), 'willReturnOnConsecutiveCalls' ),
                $testCase['providedRnd']
            );
            $idProvider = new IdProvider( $this->objectsService, $randomProvider, 'ap' );
            $request = Request::create( 'https://example.com' );
            if ( array_key_exists( 'path', $testCase ) ) {
                $id = $idProvider->getId( $request, $testCase['path'] );
            } else {
                $id = $idProvider->getId( $request );
            }
            $this->assertEquals( $testCase['expectedId'], $id, "Error on test $testCase[id]" );
        }
    }
}

