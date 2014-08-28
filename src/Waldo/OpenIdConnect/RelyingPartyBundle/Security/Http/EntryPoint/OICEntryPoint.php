<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Http\EntryPoint;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * OICEntryPoint
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICEntryPoint implements AuthenticationEntryPointInterface
{

    /**
     * @var HttpKernelInterface
     */
    protected $httpKernel;

    /**
     * @var HttpUtils
     */
    protected $httpUtils;

    /**
     * @var string
     */
    protected $loginPath;

    /**
     * @var Boolean
     */
    protected $useForward;

    /**
     * @var ResourceOwnerInterface 
     */
    protected $resourceOwner;


    /**
     * Constructor
     *
     * @param HttpUtils $httpUtils
     * @param string $loginPath
     * @param Boolean $useForward
     */
    public function __construct(HttpKernelInterface $kernel, HttpUtils $httpUtils, $loginPath, $useForward = false, ResourceOwnerInterface $resourceOwner)
    {
        $this->httpKernel = $kernel;
        $this->httpUtils = $httpUtils;
        $this->loginPath = $loginPath;
        $this->useForward = (Boolean) $useForward;
        $this->resourceOwner = $resourceOwner;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {

        $this->resourceOwner->getAuthenticationEndpointUrl($request);
//        
//        if ($this->useForward) {
//            $subRequest = $this->httpUtils->createRequest($request, $this->loginPath);
//            $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
//            if (200 === $response->getStatusCode()) {
//                $response->headers->set('X-Status-Code', 401);
//            }
//            return $response;
//        }
//        
//        //TODO redirect to the OP Whit a ResourceOwner
//        return $this->httpUtils->createRedirectResponse($request, $this->loginPath);
    }

}
