<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends Controller
{

    public function connectAction()
    {
    }

    public function redirectToServiceAction(Request $request)
    {
        $this->get('waldo_oic_rp.resource_owner.generic')->authenticateUser($request);
        
        return $this->redirect($this->generateUrl("_private_page"));
    }

}
