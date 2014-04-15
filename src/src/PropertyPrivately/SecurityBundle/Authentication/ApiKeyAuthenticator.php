<?php

/**
 * Property Privately API
 *
 * (c) Elliot Wright, 2014 <wright.elliot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PropertyPrivately\SecurityBundle\Authentication;

use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use PropertyPrivately\SecurityBundle\User\ApiKeyUserProvider;

/**
 * API Key Authenticator
 */
class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * @var ApiKeyUserProvider
     */
    protected $userProvider;

    /**
     * Constructor
     *
     * @param ApiKeyUserProvider $userProvider
     */
    public function __construct(ApiKeyUserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Create token to authenticate
     *
     * @param  Request $request
     * @param  string  $providerKey
     * @return PreAuthenticatedToken
     *
     * @throws BadCredentialsException
     */
    public function createToken(Request $request, $providerKey)
    {
        if ( ! $request->headers->has('X-API-App-Secret') || empty($request->headers->get('X-API-App-Secret'))) {
            throw new BadCredentialsException('No API app secret found.');
        }

        if ( ! $request->headers->has('X-API-Key') || empty($request->headers->get('X-API-Key'))) {
            throw new BadCredentialsException('No API key found.');
        }

        return new PreAuthenticatedToken(
            'anon.',
            [
                'apiAppSecret' => $request->headers->get('X-API-App-Secret'),
                'apiKey'       => $request->headers->get('X-API-Key')
            ],
            $providerKey
        );
    }

    /**
     * Authenticate a token
     *
     * @param  TokenInterface        $token
     * @param  UserProviderInterface $userProvider
     * @param  string                $providerKey
     * @return PreAuthenticatedToken
     *
     * @throws AuthenticationException
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $credentials  = $token->getCredentials();
        $apiKey       = $credentials['apiKey'];
        $apiAppSecret = $credentials['apiAppSecret'];


        $username = $this->userProvider->getUsernameForApiAppSecretAndApiKey($apiAppSecret, $apiKey);

        if ( ! $username) {
            throw new AuthenticationException(
                sprintf('API Key "%s" does not exist.', $apiKey)
            );
        }

        $user = $this->userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * Supports which token?
     *
     * @param  TokenInterface $token
     * @param  string         $providerKey
     * @return boolean
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}
