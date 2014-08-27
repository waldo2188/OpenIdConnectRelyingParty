<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Http\Firewall\Token;

use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Code\Authentication\Token\OICToken;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * OpenId Connect Listener
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICListener extends AbstractAuthenticationListener
{

    /**
     * {@inheritDoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        //TODO extract idToken and accessToken
        $idToken = null;
        $accessToken = null;
        
        $token = new OICToken($idToken, $accessToken);
                
        return $this->authenticationManager->authenticate($token);
    }

}