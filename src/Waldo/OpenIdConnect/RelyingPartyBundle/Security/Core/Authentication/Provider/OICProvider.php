<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Provider;

use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Code\Authentication\Token\OICToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
//use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * OICProvider
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;
    
    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }
    
    /**
     * {@inheritDoc}
     */
    public function authenticate(TokenInterface $token)
    {
        echo "<pre>:OICProvider";
        var_dump($token);
        echo "</pre>";

        exit;
        $user = $this->userProvider->loadUserByUsername($token->getUsername());

        return new OICToken($token->getIdToken(), $token->getAccessToken(), $user->getRoles());

//        throw new Authenti<cationException('The authentication failed.');
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TokenInterface $token)
    {
        echo "<pre>:OICProvider";
        var_dump($token);
        echo "</pre>";

        exit;
        return $token instanceof OICToken;
    }

}
