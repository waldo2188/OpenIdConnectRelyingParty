<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('WaldoOpenIdConnectRelyingPartyBundle:Default:index.html.twig', array('name' => $name));
    }
}
