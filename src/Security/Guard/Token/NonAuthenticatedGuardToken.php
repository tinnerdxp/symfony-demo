<?php

namespace Symfony\Component\Security\Guard\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * The token used by the guard auth system before authentication
 *
 * The GuardAuthenticationListener creates this, which is then consumed
 * immediately by the GuardAuthenticationProvider. If authentication is
 * successful, a different authenticated token is returned
 */
class NonAuthenticatedGuardToken extends AbstractToken
{
    private $credentials;
    private $guardProviderKey;

    /**
     * @param mixed  $credentials
     * @param string $guardProviderKey Unique key that bind this token to a specific GuardAuthenticator
     */
    public function __construct($credentials, $guardProviderKey)
    {
        $this->credentials = $credentials;
        $this->guardProviderKey = $guardProviderKey;

        parent::__construct(array());
    }

    public function getGuardProviderKey()
    {
        return $this->guardProviderKey;
    }

    /**
     * Returns the user credentials, which might be an array of anything you
     * wanted to put in there (e.g. username, password, favoriteColor).
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    public function isAuthenticated()
    {
        return false;
    }

    public function setAuthenticated($authenticated)
    {
        throw new \Exception('The NonAuthenticatedGuardToken is *always* not authenticated');
    }
}