<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Provider;

use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\User\OICUserProvider;

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
     
        echo "<pre>OICProvider token:1:";
        var_dump($token);
        echo "</pre>";



        $user = $this->userProvider->loadUserByUsername($token->getUsername());

        if($user->getUsername() === $token->getUsername()) {
            
            if($this->userProvider instanceof OICUserProvider) {
//                $user = $token->getUser();
                
                echo "<pre>OICProvider token:";
                var_dump($token);
                echo "</pre>";exit;
                
            }
            
            $relodedToken = new OICToken($user->getRoles());
            $relodedToken->setAccessToken($token->getAccessToken());
            $relodedToken->setIdToken($token->getIdToken());
            $relodedToken->setRefreshToken($token->getRefreshToken());
            $relodedToken->setUser($user);
            
            return $relodedToken;
        }
        
        throw new AuthenticationException('The OpenID Connect authentication failed.');
    }

    /**
     * {@inheritDoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OICToken;
    }

}
