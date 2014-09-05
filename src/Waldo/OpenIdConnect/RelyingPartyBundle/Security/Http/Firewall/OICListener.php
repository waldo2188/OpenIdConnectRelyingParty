<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Http\Firewall;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * OpenId Connect Listener
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICListener extends AbstractAuthenticationListener
{

    /**
     * @var ResourceOwnerInterface  
     */
    private $resourceOwner;
    
    /**
     * @var array
     */
    private $config;

    /**
     * @param ResourceOwnerInterface $resourceOwner
     */
    public function setResourceOwner(ResourceOwnerInterface $resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }
    
    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        
        echo "<pre>OICListener : AbstractAuthenticationListener";
        var_dump($config);
        echo "</pre>";
exit;


        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        
        if($request->query->count() == 0) {
            $uri = $this->resourceOwner->getAuthenticationEndpointUrl($request);
        } else {
            $this->resourceOwner->setConfig($this->config);
            $this->resourceOwner->authenticateUser($request);
        }       

        return new RedirectResponse($uri);
    }

}
