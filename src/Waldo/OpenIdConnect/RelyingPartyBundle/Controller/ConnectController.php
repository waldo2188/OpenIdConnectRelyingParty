<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends Controller
{

    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function connectAction(Request $request)
    {
        echo "<pre>ConnectController:connectAction:";
        var_dump($request);
        echo "</pre>";
        exit;
    }

    public function redirectToServiceAction(Request $request)
    {
        echo "<pre>ConnectController:connectAction:";
        var_dump($request);
        echo "</pre>";
        exit;
    }

}
