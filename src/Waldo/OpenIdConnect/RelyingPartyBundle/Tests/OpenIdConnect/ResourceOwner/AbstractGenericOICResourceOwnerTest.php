<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Tests\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner\GenericOICResourceOwner;
use Symfony\Component\HttpFoundation\Request;
/**
 * GenericOICResourceOwner
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class AbstractGenericOICResourceOwnerTest extends \PHPUnit_Framework_TestCase
{

    
    public function testShouldAuthenticationEndpointUrl()
    {
        $resourseOwner = $this->createGenericOICResourceOwner('http://localhost/login_check');
        $request = new Request();
        
        $expected = "http://oic.com/auth?client_id=my_client_id&display=page&max_age=300&redirect_uri=http%3A%2F%2Flocalhost%2Flogin_check&response_type=code&scope=openid%20profil%20other&ui_locales=F_fr";
        $res = $resourseOwner->getAuthenticationEndpointUrl($request, 'plop_uri', array('display' => 'page'));
        
        $this->assertEquals($expected, $res);            
    }
    
    public function testShouldReturnTokenEndpointUrl()
    {
        $resourseOwner = $this->createGenericOICResourceOwner();
        
        $this->assertEquals("http://oic.com/token", $resourseOwner->getTokenEndpointUrl());
    }
    
    public function testShouldReturnUserinfoEndpointUrl()
    {
        $resourseOwner = $this->createGenericOICResourceOwner();
        
        $this->assertEquals("http://oic.com/userinfo", $resourseOwner->getUserinfoEndpointUrl());
    }
    
    public function testIsAuthenticated()
    {
        $resourseOwner = $this->createGenericOICResourceOwner(null, $this->getMock("Symfony\Component\Security\Core\Authentication\Token\TokenInterface"));
        
        $this->assertInstanceOf("Symfony\Component\Security\Core\Authentication\Token\TokenInterface",
                $resourseOwner->isAuthenticated());
    }
    
    public function testIsNotAuthenticated()
    {
        $resourseOwner = $this->createGenericOICResourceOwner();
        
        $this->assertFalse($resourseOwner->isAuthenticated());
    }
    
    public function testShouldAuthenticateUser()
    {
        $responseHandler = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler')
                ->disableOriginalConstructor()->getMock();
        
        $jwt = new \JOSE_JWT(array('sub'=>'amy.pond'));
        
        $responseHandler->expects($this->once())
                ->method('handleTokenAndAccessTokenResponse')
                ->willReturn(array(
                    'access_token' => 'access_token_value',
                    'refresh_token' => 'refresh_token_value',
                    'expires_in' => 'expires_in_value',
                    'id_token' => $jwt
        ));
        $responseHandler->expects($this->once())
                ->method('handleEndUserinfoResponse')
                ->willReturn(array(
                    'sub' => 'amy.pond',
                    'name' => 'Amelia Pond',
                    'phone_number' => '123-456-7890'
        ));

        $resourseOwner = $this->createGenericOICResourceOwner(null, null, true, $responseHandler);
        
        $request = new Request();
        $request->query->set('code', "anOicCode");
        
        $res = $resourseOwner->authenticateUser($request);
        
        $this->assertInstanceOf("Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken", $res);
        $this->assertEquals('amy.pond', $res->getUsername());
    }
    
    /**
     * @expectedException Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidIdTokenException
     */
    public function testShouldFailAuthenticateUser()
    {
        $responseHandler = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler')
                ->disableOriginalConstructor()->getMock();
        
        $jwt = new \JOSE_JWT(array('sub'=>'amy.pond'));
        
        $resourseOwner = $this->createGenericOICResourceOwner(null, null, false);
        
        $request = new Request();
        $request->query->set('code', "anOicCode");
        
        $resourseOwner->authenticateUser($request);
    }
    
    /**
     * @expectedException Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidRequestException
     * @expectedExceptionMessage "no such access_token"
     */
    public function testShouldFailAuthenticateUserNoAccessToken()
    {
        $responseHandler = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler')
                ->disableOriginalConstructor()->getMock();
        
        $jwt = new \JOSE_JWT(array('sub'=>'amy.pond'));
        
        $resourseOwner = $this->createGenericOICResourceOwner(null, null, true);
        
        $request = new Request();
        $request->query->set('code', "anOicCode");
        
        $resourseOwner->authenticateUser($request);
    }

    /**
     * @expectedException Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidIdTokenException
     * @expectedExceptionMessage "The sub value is not equal"
     */
    public function testShouldFailAuthenticateUserSubValueNotEqual()
    {
        $responseHandler = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler')
                ->disableOriginalConstructor()->getMock();
        
        
        $jwt = new \JOSE_JWT(array('sub'=>'amy.pond'));
        
        $responseHandler->expects($this->once())
                ->method('handleTokenAndAccessTokenResponse')
                ->willReturn(array(
                    'access_token' => 'access_token_value',
                    'refresh_token' => 'refresh_token_value',
                    'expires_in' => 'expires_in_value',
                    'id_token' => $jwt
        ));
        $responseHandler->expects($this->once())
                ->method('handleEndUserinfoResponse')
                ->willReturn(array(
                    'sub' => 'rory.williams',
        ));
        
        $resourseOwner = $this->createGenericOICResourceOwner(null, null, true, $responseHandler);
        
        $request = new Request();
        $request->query->set('code', "anOicCode");
        
        $resourseOwner->authenticateUser($request);
    }
    
    public function testShouldReturnName()
    {
        $resourseOwner = $this->createGenericOICResourceOwner();
        
        $this->assertEquals("generic", $resourseOwner->getName());
    }
    
    
    
    private function createGenericOICResourceOwner(
            $httpUtilsRV = "", 
            $securityContextReturnValue = null, 
            $idTokenValidatorRV= true,
            $responseHandler = null)
    {
        $securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
                ->disableOriginalConstructor()->getMock();
        $securityContext->expects($this->any())
                ->method('getToken')
                ->willReturn($securityContextReturnValue);
                
        
        $httpUtils = $this->getMockBuilder('\Symfony\Component\Security\Http\HttpUtils')
                ->disableOriginalConstructor()->getMock();
        $httpUtils->expects($this->atMost(2))
                ->method("generateUri")
                ->willReturn($httpUtilsRV);
                
        $httpClient = $this->getMockBuilder("Buzz\Client\AbstractCurl")
                ->disableOriginalConstructor()->getMock();
        
        $idTokenValidator = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Constraint\ValidatorInterface')
                ->disableOriginalConstructor()->getMock();
        $idTokenValidator->expects($this->any())
                ->method('isValid')
                ->willReturn($idTokenValidatorRV);
        
        $responseHandler = $responseHandler ? $responseHandler : $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler')
                ->disableOriginalConstructor()->getMock();
               
        
        $nonceHelper = $this->getMockBuilder('Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\NonceHelper')
                ->disableOriginalConstructor()->getMock();
        
        return new GenericOICResourceOwner(
                $securityContext,
                $httpUtils,
                $httpClient,
                $idTokenValidator,
                $responseHandler,
                $nonceHelper,
                array(
                    "client_id" => "my_client_id",
                    "client_secret" => "my_client_secret",
                    "scope" => "openid profil other",
                    "authentication_ttl" => "300",
                    "ui_locales" => "F_fr",
                    "endpoints_url" => array(
                        "authorisation" => "http://oic.com/auth",
                        "token" => "http://oic.com/token",
                        "userinfo" => "http://oic.com/userinfo"
                        )
                    )
                );
    }            
            
}
