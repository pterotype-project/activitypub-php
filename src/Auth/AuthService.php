<?php
namespace ActivityPub\Auth;

use ActivityPub\Entities\ActivityPubObject;
use Symfony\Component\HttpFoundation\Request;

class AuthService
{
    public function isAuthorized( Request $request,
                                  ActivityPubObject $object )
    {
        if ( ! $this->hasAudience( $object ) ) {
            return true;
        }
        $audience = $this->getAudience( $object );
        if ( in_array( 'https://www.w3.org/ns/activitystreams#Public', $audience ) ) {
            return true;
        }
        return $request->attributes->has( 'actor' ) &&
            in_array( $request->attributes->get( 'actor' ), $audience );
    }

    private function hasAudience( ActivityPubObject $object )
    {
        $arr = $object->asArray( 0 );
        return array_key_exists( 'audience', $arr ) ||
            array_key_exists( 'to', $arr ) ||
            array_key_exists( 'bto', $arr ) ||
            array_key_exists( 'cc', $arr ) ||
            array_key_exists( 'bcc', $arr );
    }

    /**
     * Returns an array of all of the $object's audience actors, i.e.
     * the contents of the to, bto, cc, bcc, and audience fields, as
     * well as the actor who created to object
     *
     * @param ActivityPubObject $object
     * @return array The audience members, collapsed to an array of ids
     */
    private function getAudience( ActivityPubObject $object )
    {
        // TODO do I need to traverse the inReplyTo chain here?
        $objectArr = $object->asArray( 0 );
        $audience = array();
        foreach( array( 'to', 'bto', 'cc', 'bcc', 'audience', 'attributedTo', 'actor' )
                 as $attribute ) {
            $audience = $this->checkAudienceAttribute( $audience, $attribute, $objectArr );
        }
        return $audience;
    }

    private function checkAudienceAttribute( $audience, $attribute, $objectArr )
    {
        if ( array_key_exists( $attribute, $objectArr ) ) {
            $audienceValue = $objectArr[$attribute];
            if ( ! is_array( $audienceValue ) ) {
                $audienceValue = array( $audienceValue );
            }
            return array_merge( $audience, $audienceValue );
        } else {
            return $audience;
        }
    }
}
?>
