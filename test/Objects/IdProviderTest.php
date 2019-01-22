<?php
namespace ActivityPub\Test\Objects;

use ActivityPub\Objects\IdProvider;
use ActivityPub\Objects\ObjectsService;
use ActivityPub\Utils\RandomProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class IdProviderTest extends TestCase
{
    const EXISTING_ID_STR = 'exists';

    private $objectsService;

    public function setUp()
    {
        $this->objectsService = $this->createMock( ObjectsService::class );
        $this->objectsService->method( 'query' )->will( $this->returnCallback( function( $query) {
            $existsId = sprintf( 'https://example.com/objects/%s', self::EXISTING_ID_STR );
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
                'expectedId' => 'https://example.com/objects/foo',
            ),
            array(
                'id' => 'checksForExisting',
                'providedRnd' => array( self::EXISTING_ID_STR, 'bar' ),
                'expectedId' => 'https://example.com/objects/bar',
            ),
            array(
                'id' => 'addsPath',
                'providedRnd' => array( 'foo' ),
                'path' => 'notes',
                'expectedId' => 'https://example.com/notes/foo',
            ),
        );
        foreach ( $testCases as $testCase ) {
            $randomProvider = $this->createMock( RandomProvider::class );
            call_user_func_array(
                array( $randomProvider->method( 'randomString' ), 'willReturnOnConsecutiveCalls' ),
                $testCase['providedRnd']
            );
            $idProvider = new IdProvider( $this->objectsService, $randomProvider );
            $request = Request::create( 'https://example.com' );
            $id = '';
            if ( array_key_exists( 'path', $testCase ) ) {
                $id = $idProvider->getId( $request, $testCase['path'] );
            } else {
                $id = $idProvider->getId( $request );
            }
            $this->assertEquals( $testCase['expectedId'], $id, "Error on test $testCase[id]" );
        }
    }
}
?>
