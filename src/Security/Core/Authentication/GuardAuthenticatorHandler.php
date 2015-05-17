<?php

namespace Symfony\Component\Security\Core\Authentication;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class GuardAuthenticatorHandler
{
    private $tokenStorage;

    private $dispatcher;

    public function __construct(TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * Authenticates the given token in the system
     *
     * @param TokenInterface $token
     * @param Request $request
     */
    public function authenticateWithToken(TokenInterface $token, Request $request)
    {
        $this->tokenStorage->setToken($token);

        if (null !== $this->dispatcher) {
            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->dispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
        }
    }

    /**
     * Returns the "on success" response for the given GuardAuthenticator
     *
     * @param GuardAuthenticatorInterface $guardAuthenticator
     * @param Request $request
     * @param TokenInterface $token
     * @param $providerKey
     * @return null|Response
     */
    public function handleAuthenticationSuccess(GuardAuthenticatorInterface $guardAuthenticator, Request $request, TokenInterface $token, $providerKey)
    {
        $response = $guardAuthenticator->onAuthenticationSuccess($request, $token, $providerKey);

        // check that it's a Response or null
        if ($response instanceof Response || null === $response) {
            return $response;
        }

        throw new \UnexpectedValueException(sprintf(
            'The %s::onAuthenticationSuccess method must return null or a Response object. You returned %s',
            get_class($guardAuthenticator),
            is_object($response) ? get_class($response) : gettype($response)
        ));
    }

    /**
     * Convenience method for authenticating the user and returning the
     * Response *if any* for success
     *
     * @param UserInterface $user
     * @param GuardAuthenticatorInterface $authenticator
     * @param Request $request
     * @param $providerKey
     * @return Response|null
     */
    public function authenticateUserAndHandleSuccess(UserInterface $user, GuardAuthenticatorInterface $authenticator, Request $request, $providerKey)
    {
        // create an authenticated token for the User
        $token = $authenticator->createAuthenticatedToken($user, $providerKey);
        // authenticate this in the system
        $this->authenticateWithToken($token, $request);

        // return the success metric
        return $this->handleAuthenticationSuccess($authenticator, $request, $token, $providerKey);
    }

    /**
     * Handles an authentication failure and returns the Response for the
     * GuardAuthenticator
     *
     * @param AuthenticationException $authenticationException
     * @param GuardAuthenticatorInterface $guardAuthenticator
     * @param Request $request
     * @return null|Response
     */
    public function handleAuthenticationFailure(AuthenticationException $authenticationException, GuardAuthenticatorInterface $guardAuthenticator, Request $request)
    {
        $this->tokenStorage->setToken(null);

        $response = $guardAuthenticator->onAuthenticationFailure($request, $authenticationException);
        if ($response instanceof Response || null === $response) {
            // returning null is ok, it means they want the request to continue
            return $response;
        }

        throw new \UnexpectedValueException(sprintf(
            'The %s::onAuthenticationFailure method must return null or a Response object. You returned %s',
            get_class($guardAuthenticator),
            is_object($response) ? get_class($response) : gettype($response)
        ));
    }
}