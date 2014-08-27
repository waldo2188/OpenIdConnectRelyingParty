<?php

namespace Waldo\ExempleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }
    
    
    /**
     * @Secure(roles="ROLE_ADMIN")
     * @Route("/private", name="_private_page")
     * @Template()
     */
    public function privateResouceAction()
    {
        return array();
    }
}
