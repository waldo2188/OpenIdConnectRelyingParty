<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Authentication\Token\OICToken;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\User\OICUser;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Constraint\ValidatorInterface;
use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\Response\OICResponseHandler;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidIdTokenException;
use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidNonceException;
use Buzz\Client\AbstractCurl;
use Buzz\Message\Request as HttpClientRequest;
use Buzz\Message\Response as HttpClientResponse;
use Buzz\Message\RequestInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
     * @var SessionInterface
     */
    private $session;

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
     * @var array
     */
    private $options = array();

    public function __construct(SecurityContext $securityContext, SessionInterface $session,
            HttpUtils $httpUtils, AbstractCurl $httpClient, ValidatorInterface $idTokenValidator,
            OICResponseHandler $responseHandler, $options)
    {
        $this->securityContext = $securityContext;
        $this->session = $session;
        $this->httpUtils = $httpUtils;
        $this->httpClient = $httpClient;
        $this->idTokenValidator = $idTokenValidator;
        $this->responseHandler = $responseHandler;


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
            'nonce' => $this->setNonceInSession($request->getClientIp()),
            'state' => $this->setNonceInSession($request->getClientIp(), "state"),
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
     * Check if user is already authenticated
     * @return TokenInterface | boolean
     */
    public function isAuthenticated()    
    {
        $token = $this->securityContext->getToken();
        if($token !== null && $token instanceof TokenInterface) {
            return $token;
        }
        return false;
    }

    public function authenticateUser(Request $request)
    {
        $this->responseHandler->checkForError($request->query->all());

        $code = $request->query->get('code');

        $oicToken = new OICToken();

        $this->getIdTokenAndAccessToken($request, $oicToken, $code);

        $this->getEndUserinfo($request, $oicToken);

        $oicToken->setUser(new OICUser($oicToken->getUserinfo("sub"), $oicToken->getUserinfo()));

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
        $this->checkStateAndNonce($request);


        $tokenEndpointUrl = $this->getTokenEndpointUrl();

        $postParameters = array(
            'grant_type' => 'authorization_code',
            'code' => $code
        );

        $postParameters = http_build_query($postParameters);
        $contentLength = strlen($postParameters);

        $headers = array(
            'User-Agent: WaldoOICRelyingPartyhBundle',
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . $contentLength
        );

        $request = new HttpClientRequest(RequestInterface::METHOD_POST, $tokenEndpointUrl);
        $request->setHeaders($headers);
        $request->setContent($postParameters);

        $response = new HttpClientResponse();

        $this->httpClient->setOption(CURLOPT_USERPWD, $this->options['client_id'] . ':' . $this->options['client_secret']);
        $this->httpClient->send($request, $response);

        $content = $this->responseHandler->handleTokenAndAccessTokenResponse($response);

        // Apply validation describe here -> http://openid.net/specs/openid-connect-basic-1_0.html#IDTokenValidation
        if (!$this->idTokenValidator->isValid($content['id_token'])) {
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


        if ($content['sub'] === $oicToken->getIdToken()->claims['sub']) {
            $oicToken->setRawUserinfo($content);
            return;
        }

        throw new InvalidIdTokenException("The sub value is not equal");
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return null;
    }

    /**
     * Generate a nonce/state value.
     * If rsa keys is set, the nonce value is crypted and the methode return a 
     * JOSE_JWE object
     * 
     * If no rsa keys, the methode return a string
     * 
     * @param string $uniqueValue 
     * @return string|\JOSE_JWE 
     */
    protected function generateNonce($uniqueValue)
    {
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        $hash = bin2hex(mcrypt_create_iv(12, MCRYPT_DEV_URANDOM));
        $nonce = sprintf("%s-%s", $hash, \JOSE_URLSafeBase64::encode($uniqueValue));
        $nonceEnc = \JOSE_URLSafeBase64::encode($nonce);

        if (strlen($nonceEnc) > 255) {
            $nonceEnc = substr($nonceEnc, 0, 254);
        }

        if ($this->options['public_rsa_path'] !== null) {
            $publicKey = file_get_contents($this->options['public_rsa_path']);

            $jweEncode = new \JOSE_JWE($nonceEnc);

            $nonceEnc = $jweEncode->encrypt($publicKey);
        }

        return $nonceEnc;
    }

    /**
     * Check if the nonce/state value is the right one
     * 
     * @param string $type nonce ou state
     * @param type $uniqueValue the same as this passed to the generateNonce mehode
     * @param type $responseNonce the nonce reply by the OpenID Connect Provider
     * @return boolean
     * @throws InvalidNonceException
     */
    protected function isNonceValid($type, $uniqueValue, $responseNonce)
    {
        $referenceNonce = unserialize($this->session->get("auth.oic." . $type));
        $this->session->remove("auth.oic." . $type);

        if ($referenceNonce instanceof \JOSE_JWE && $this->options['private_rsa_path'] !== null) {

            $responseNonce = hex2bin($responseNonce);

            if ($referenceNonce->cipher_text !== $responseNonce) {
                throw new InvalidNonceException("Nonces values are not equal");
            }

            $privateKey = file_get_contents($this->options['private_rsa_path']);

            $referenceNonce->cipher_text = $responseNonce;

            try {
                $referenceNonce->decrypt($privateKey);
            } catch (\JOSE_Exception_DecryptionFailed $ex) {
                throw new InvalidNonceException("Nonce cannot be decrypted");
            }

            $referenceNonce = $referenceNonce->plain_text;
        }

        $referenceNonce = \JOSE_URLSafeBase64::decode($referenceNonce);
        
        $referenceNonce = split("-", $referenceNonce);
        if(count($referenceNonce) == 0) {
            throw new InvalidNonceException("Nonce value is corrupted");
        }
        
        $referenceNonce = \JOSE_URLSafeBase64::decode($referenceNonce[1]);

        if ($referenceNonce !== $uniqueValue) {
            throw new InvalidNonceException("Nonce value is corrupted");
        }

        return true;
    }

    /**
     * this method generate a nonce/state value, store it in a session and return
     * the string to put in http request.
     * 
     * @param type $uniqueValue
     * @param type $type
     * @return string
     */
    protected function setNonceInSession($uniqueValue, $type = "nonce")
    {
        $nonce = $this->generateNonce($uniqueValue);
        $this->session->set("auth.oic." . $type, serialize($nonce));

        if ($nonce instanceof \JOSE_JWE) {
            return \JOSE_URLSafeBase64::encode(bin2hex($nonce->cipher_text));
        }

        return $nonce;
    }

    /**
     * Check validity for nonce and state value
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @throws InvalidNonceException
     */
    protected function checkStateAndNonce(Request $request)
    {
        foreach (array("state", "nonce") as $type) {
            if ($request->query->has($type)) {
                if (!$this->isNonceValid($type, $request->getClientIp(), $request->query->get($type))) {
                    throw new InvalidNonceException(
                    sprintf("the %s value is not the one expected", $type)
                    );
                }
            } else {
                $this->session->remove("auth.oic." . $type);
            }
        }
    }
}
