<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends Controller
{
    public function redirectToServiceAction(Request $request)
    {
        $redirectUri = $this->get('waldo_oic_rp.resource_owner.generic')->authenticateUser($request);
        
        return $this->redirect($redirectUri);
    }

}
