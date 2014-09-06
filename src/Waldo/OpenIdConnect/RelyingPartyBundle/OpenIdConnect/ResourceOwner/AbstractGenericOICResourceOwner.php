<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\User\OICUser;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Constraint\ValidatorInterface;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidIdTokenException;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\NonceHelper;
use Buzz\Client\AbstractCurl;
use Buzz\Message\Request as HttpClientRequest;
use Buzz\Message\Response as HttpClientResponse;
use Buzz\Message\RequestInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
     * @var OICResponseHandler
     */
    private $responseHandler;

    /**
     * @var NonceHelper
     */
    private $nonceHelper;
    
    /**
     * @var array
     */
    private $options = array();

    public function __construct(SecurityContext $securityContext,
            HttpUtils $httpUtils, AbstractCurl $httpClient, 
            ValidatorInterface $idTokenValidator, 
            OICResponseHandler $responseHandler, 
            NonceHelper $nonceHelper, $options)
    {
        $this->securityContext = $securityContext;
        $this->httpUtils = $httpUtils;
        $this->httpClient = $httpClient;
        $this->idTokenValidator = $idTokenValidator;
        $this->responseHandler = $responseHandler;
        $this->nonceHelper = $nonceHelper;


        if (array_key_exists("endpoints_url", $options)) {
            $options["authorisation_endpoint_url"] = $options["endpoints_url"]["authorisation"];
            $options["token_endpoint_url"] = $options["endpoints_url"]["token"];
            $options["userinfo_endpoint_url"] = $options["endpoints_url"]["userinfo"];
            unset($options["endpoints_url"]);
        }

        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthenticationEndpointUrl(Request $request, $redirectUri = 'login_check', array $extraParameters = array())
    {
        $urlParameters = array(
            'client_id' => $this->options['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->httpUtils->generateUri($request, $redirectUri),
            'scope' => $this->options['scope'],
            'nonce' => $this->nonceHelper->buildNonceValue($request->getClientIp()),
            'state' => $this->nonceHelper->buildNonceValue($request->getClientIp(), "state"),
            'max_age' => $this->options['authentication_ttl']
        );

        if ($this->options['authentication_ttl'] != null && $this->options['authentication_ttl'] > 0) {
            $urlParameters['max_age'] = $this->options['authentication_ttl'];
        }

        $parametersToAdd = array('display', 'prompt', 'ui_locales');
        foreach ($parametersToAdd as $param) {
            if (array_key_exists($param, $this->options) && $this->options[$param] !== null) {
                $urlParameters[$param] = $this->options[$param];
            }
        }

        $urlParameters = array_merge($urlParameters, $extraParameters);

        $httpRequest = new Request();
        $authenticationUri = $httpRequest->create(
                        $this->options['authorisation_endpoint_url'], RequestInterface::METHOD_GET, $urlParameters)
                ->getUri();

        return $authenticationUri;
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

    /**
     * {@inheritDoc}
     */
    public function isAuthenticated()
    {

        $token = $this->securityContext->getToken();

        if ($token !== null && $token instanceof TokenInterface) {
            return $token;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticateUser(Request $request)
    {
        $this->responseHandler->checkForError($request->query->all());

        $code = $request->query->get('code');

        $oicToken = new OICToken();

        $this->getIdTokenAndAccessToken($request, $oicToken, $code);

        $this->getEndUserinfo($request, $oicToken);

        return $oicToken;
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
        $this->nonceHelper->checkStateAndNonce($request);

        $tokenEndpointUrl = $this->getTokenEndpointUrl();

        $postParameters = array(
            'grant_type' => 'authorization_code',
            'code' => $code
        );

        $postParametersQuery = http_build_query($postParameters);

        $headers = array(
            'User-Agent: WaldoOICRelyingPartyhBundle',
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($postParametersQuery)
        );

        $request = new HttpClientRequest(RequestInterface::METHOD_POST, $tokenEndpointUrl);
        $request->setHeaders($headers);
        $request->setContent($postParametersQuery);

        $response = new HttpClientResponse();

        $this->httpClient->setOption(CURLOPT_USERPWD, $this->options['client_id'] . ':' . $this->options['client_secret']);
        $this->httpClient->send($request, $response);

        $content = $this->responseHandler->handleTokenAndAccessTokenResponse($response);

        // Apply validation describe here: http://openid.net/specs/openid-connect-basic-1_0.html#IDTokenValidation
        if (!$this->idTokenValidator->isValid($content['id_token'])) {
            throw new OICException\InvalidIdTokenException();
        }

        $oicToken->setRawTokenData($content);
    }

    /**
     * Call the OpenId Connect Provider to get userinfo against an access_token
     * 
     * @see http://openid.net/specs/openid-connect-basic-1_0.html#UserInfo
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken $oicToken
     */
    protected function getEndUserinfo(Request $request, OICToken $oicToken)
    {
        if ($oicToken->getAccessToken() === null) {
            throw new OICException\InvalidRequestException("no such access_token");
        }

        $headers = array(
            'Authorization: Bearer ' . $oicToken->getAccessToken()
        );

        $request = new HttpClientRequest(
                RequestInterface::METHOD_POST, $this->getUserinfoEndpointUrl());

        $request->setHeaders($headers);

        $response = new HttpClientResponse();

        $this->httpClient->send($request, $response);


        $content = $this->responseHandler->handleEndUserinfoResponse($response);

        // Check if the sub value return by the OpenID connect Provider is the 
        // same as previous. If Not, that isn't good...
        if ($content['sub'] !== $oicToken->getIdToken()->claims['sub']) {
            throw new InvalidIdTokenException("The sub value is not equal");
        }
        
        $oicToken->setRawUserinfo($content);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return null;
    }
}
