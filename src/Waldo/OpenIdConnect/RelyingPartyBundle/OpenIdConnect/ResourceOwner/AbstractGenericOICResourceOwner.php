<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\User\OICUser;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception as OICException;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Constraint\ValidatorInterface;
use Buzz\Client\AbstractCurl;
use Buzz\Message\Request as HttpClientRequest;
use Buzz\Message\Response as HttpClientResponse;
use Buzz\Message\RequestInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use JOSE_JWT;

/**
 * GenericOICResourceOwner
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
abstract class AbstractGenericOICResourceOwner implements ResourceOwnerInterface
{

    /**
     * @var SecurityContext 
     */
    private $securityContext;

    /**
     * @var HttpUtils 
     */
    private $httpUtils;
    
    /**
     * @var AbstractCurl
     */
    private $httpClient;
    
    /**
     * @var ValidatorInterface
     */
    private $idTokenValidator;

    /**
     * @var array
     */
    private $options = array();

    function __construct(SecurityContext $securityContext, HttpUtils $httpUtils, AbstractCurl $httpClient, ValidatorInterface $idTokenValidator, $options)
    {
        $this->securityContext = $securityContext;
        $this->httpUtils = $httpUtils;
        $this->httpClient = $httpClient;
        $this->idTokenValidator = $idTokenValidator;

        if(array_key_exists("endpoints_url", $options)) {
            $options["authorisation_endpoint_url"] = $options["endpoints_url"]["authorisation"];
            $options["token_endpoint_url"] = $options["endpoints_url"]["token"];
            $options["userinfo_endpoint_url"] = $options["endpoints_url"]["userinfo"];
            unset($options["endpoints_url"]);
        }
        
        // Resolve merged options
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        
        $options = $resolver->resolve($options);
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthenticationEndpointUrl(Request $request, $redirectUri = 'waldo_oic_rp_redirect', array $extraParameters = array())
    {        
        $urlParameters = array(
            'client_id' => $this->options['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->httpUtils->generateUri($request, $redirectUri),
            'scope' => $this->options['scope'],
            'nonce' => 'anoncevalue',
            'state' => 'astatevalue',
            'max_age' => 300
        );
        
        if($this->options['authentication_ttl'] != null && $this->options['authentication_ttl'] > 0) {
            $urlParameters['max_age'] = $this->options['authentication_ttl'];
        }
        
        $parametersToAdd = array('display', 'prompt', 'ui_locales');
        foreach($parametersToAdd as $param) {
            if(array_key_exists($param, $this->options) && $this->options[$param] !== null) {
                $urlParameters[$param] = $this->options[$param];
            }    
        }

        $urlParameters = array_merge($urlParameters, $extraParameters);
        $urlParameters = http_build_query($urlParameters);

        return $this->options['authorisation_endpoint_url'] . $urlParameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenEndpointUrl()
    {        
        return $this->options['token_endpoint_url'];
    }

    /**
     * {@inheritDoc}
     */
    public function getUserinfoEndpointUrl()
    {   
        return $this->options['userinfo_endpoint_url'];
    }
    
    

    public function authenticateUser(Request $request)
    {
        
        $this->checkForError($request->query->all());
        
        $code = $request->query->get('code');

        $oicToken = new OICToken();

        $this->getIdTokenAndAccessToken($request, $oicToken, $code);

        $this->getEndUserinfo($request, $oicToken);

        $oicToken->setUser(new OICUser($oicToken->getUserinfo("sub"), $oicToken->getUserinfo()));

        $this->securityContext->setToken($oicToken);
    }
    
    /**
     * Call the OpenID Connect Provider to exchange a code value against an id_token and an access_token
     * 
     * @see http://openid.net/specs/openid-connect-basic-1_0.html#ObtainingTokens
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken $oicToken
     * @param type $code
     */
    protected function getIdTokenAndAccessToken(Request $request, OICToken $oicToken, $code)
    {
        $tokenEndpointUrl = $this->getTokenEndpointUrl();

        $postParameters = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            
        );
        
        $postParameters = http_build_query($postParameters);
        $contentLength = strlen($postParameters);
        
        $headers = array(
                'User-Agent: WaldoOICRelyingPartyhBundle',
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . $contentLength,
            );

        $request = new HttpClientRequest(RequestInterface::METHOD_POST, $tokenEndpointUrl);
        $request->setHeaders($headers);
        $request->setContent($postParameters);       
        
        $response = new HttpClientResponse();
        
        $this->httpClient->setOption(CURLOPT_USERPWD, $this->options['client_id'].':'.$this->options['client_secret']);
        $this->httpClient->send($request, $response);

        $content = $this->handleHttpClientResponse($response);

        $content['id_token'] = \JOSE_JWT::decode($content['id_token']);
        
        // Apply validation describe here -> http://openid.net/specs/openid-connect-basic-1_0.html#IDTokenValidation
        if(!$this->idTokenValidator->isValid($content['id_token'])) {
            throw new OICException\InvalidIdTokenException();
        }
   
        $oicToken->setRawTokenData($content);
    }
    
    /**
     * Call the OpenId Connect Provider to get userInfo against an access_token
     * 
     * @see http://openid.net/specs/openid-connect-basic-1_0.html#UserInfo
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken $oicToken
     */
    protected function getEndUserinfo(Request $request, OICToken $oicToken)
    {
        if($oicToken->getAccessToken() === null) {
            throw new OICException\InvalidRequestException("no such access_token");
        }
                
        $headers = array(
                'Authorization: Bearer ' . $oicToken->getAccessToken()
            );
        
        $request = new HttpClientRequest(
                RequestInterface::METHOD_POST,
                $this->getUserinfoEndpointUrl());
        
        $request->setHeaders($headers);
                
        $response = new HttpClientResponse();
        
        $this->httpClient->send($request, $response);
        
        //TODO Validate data
        
        $content = $this->handleHttpClientResponse($response);
        
        $oicToken->setRawUserinfo($content);
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return null;
    }

    /**
     * Configure the option resolver
     *
     * @param OptionsResolverInterface $resolver
     */
    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'base_url',
            'client_id',
            'client_secret',
            'scope',
            'issuer',
            'authorisation_endpoint_url',
            'token_endpoint_url',
            'userinfo_endpoint_url',
            'http_client',
            'token_ttl',
            'authentication_ttl',
            'display',
            'ui_locales',
        ));

        $resolver->setDefaults(array(
            'scope' => null,
            'display' => null,
            'prompt' => null,
            'ui_locales' => null,
        ));
    }
    
    /**
     * Search error in header and in content of the response.
     * If an error is found an exception is throw.
     * If all is clear, the content is Json decoded (if needed) and return as an array
     * 
     * @param \Buzz\Message\Response $response
     * @return array $content
     */
    protected function handleHttpClientResponse(HttpClientResponse $response)
    {
        
        if($response->getHeader("Content-Type") == 'application/json') {
            $jsonDecode = new JsonDecode(true);
            $content = $jsonDecode->decode($response->getContent(), JsonEncoder::FORMAT);
        }
        
        if($response->getStatusCode() >= Response::HTTP_BAD_REQUEST) {
            if($bearerError = $response->getHeader("WWW-Authenticate") !== null){
                preg_match ('/^Bearer error="(.*)", error_description="(.*)"$/', $bearerError, $matches);
                $content = array('error' => $matches[1], 'error_description' => $matches[1]);                
            }
        }

        if(!$this->checkForError($content)) {
            return $content;
        }
        
        return null;
    }
    
    
    /**
     * @param array $content
     * @return boolean
     * @throws OICException\InvalidRequestException
     * @throws OICException\InvalidResponseTypeException
     * @throws OICException\InvalidAuthorizationCodeException
     * @throws OICException\InvalidClientOrSecretException
     * @throws OICException\UnsuportedGrantTypeException
     */
    protected function checkForError(array $content)
    {   
        //TODO add a log trace
        if(array_key_exists('error', $content)) {
            switch ($content['error']) {
                case 'invalid request':
                    throw new OICException\InvalidRequestException($content['error_description']);
                    break;
                case 'invalid_request':
                    throw new OICException\InvalidRequestException($content['error_description']);
                    break;
                case 'invalid_response_type':
                    throw new OICException\InvalidResponseTypeException($content['error_description']);
                    break;
                case 'invalid_authorization_code':
                    throw new OICException\InvalidAuthorizationCodeException($content['error_description']);
                    break;
                case 'invalid_client':
                    throw new OICException\InvalidClientOrSecretException($content['error_description']);
                    break;
                case 'unsupported_grant_type':
                    throw new OICException\UnsuportedGrantTypeException($content['error_description']);
                    break;
            }
        }
        
        return false;
    }
    
    
}
