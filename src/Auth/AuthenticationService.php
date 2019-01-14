<?php
namespace ActivityPub\Auth;

/**
 * The AuthenticationService class answers the question, "is this request authenticated
 * to act on behalf of this Actor?"
 *
 * It delegates most of the work to a passed-in Callable to allow library clients to
 * plug in their own authentication methods.
 */
class AuthenticationService
{
    /**
     * The Callable that is called to determine if a request is authorized for an Actor
     *
     * @var Callable
     *
     */
    private $authFunction;

    /**
     * Constructs a new AuthenticationService
     *
     * @param Callable $authFunction A Callable that should accept
     *
     */
    public function __construct( Callable $authFunction )
    {
        $this->authFunction = $authFunction;
    }
}
?>
