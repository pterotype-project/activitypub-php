<?php
namespace ActivityPub\Test\Controllers;

use ActivityPub\Controllers\GetObjectController;
use ActivityPub\Entities\ActivityPubObject;
use ActivityPub\Entities\Field;
use ActivityPub\Objects\ObjectsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPUnit\Framework\TestCase;

class GetObjectControllerTest extends TestCase
{
    const OBJECTS = array(
        'https://example.com/objects/1' => array(
            'id' => 'https://example.com/objects/1',
            'object' => array(
                'id' => 'https://example.com/objects/2',
                'type' => 'Note',
            ),
            'type' => 'Create',
        ),
    );

    private $getObjectController;

    public function setUp()
    {
        $objectsService = $this->createMock( ObjectsService::class );
        $objectsService->method( 'dereference' )->will(
            $this->returnCallback( function( $uri ) {
                if ( array_key_exists( $uri, self::OBJECTS ) ) {
                    return $this->objectFromArray( self::OBJECTS[$uri] );
                }
            })
        );
        $this->getObjectController = new GetObjectController( $objectsService );
    }

    private function objectFromArray( $array ) {
        $object = new ActivityPubObject();
        foreach ( $array as $name => $value ) {
            if ( is_array( $value ) ) {
                $child = $this->objectFromArray( $value );
                Field::withObject( $object, $name, $child );
            } else {
                Field::withValue( $object, $name, $value );
            }
        }
        return $object;
    }

    public function testItRendersPersistedObject()
    {
        $request = Request::create( 'https://example.com/objects/1' );
        $response = $this->getObjectController->handle( $request );
        $this->assertNotNull( $response );
        $this->assertEquals(
            json_encode( self::OBJECTS['https://example.com/objects/1'] ),
            $response->getContent()
        );
        $this->assertEquals( 'application/json', $response->headers->get( 'Content-Type' ) );
    }

    public function testItThrowsNotFound()
    {
        $request = Request::create( 'https://example.com/objects/notreal' );
        $this->expectException( NotFoundHttpException::class );
        $this->getObjectController->handle( $request );
    }
}
?>
