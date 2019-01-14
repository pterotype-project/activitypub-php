<?php
namespace ActivityPub\Client;

use Symfony\Component\HttpFoundation\Request;

/**
 * The HttpClient provides methods to send HTTP requests to other servers
 */
class HttpClient
{
    /**
     * Sends the $request
     *
     * @param Request $request The request
     * @return Response The HTTP response
     */
    public function send( Request $request )
    {
        // TODO implement me
        // Use symfony\psr-http-message-bridge to convert the Request to a
        // PSR-7 request which Guzzle understands
    }
}
?>
