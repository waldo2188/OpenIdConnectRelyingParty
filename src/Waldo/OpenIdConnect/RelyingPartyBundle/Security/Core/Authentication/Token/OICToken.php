<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\Security\Code\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * OpenId Connect Token
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICToken extends AbstractToken
{

    /**
     * @see http://tools.ietf.org/html/rfc6749#section-1.4
     * @var string
     */
    protected $accessToken;

    /**
     * @var array
     */
    protected $rawAccessToken;

    /**
     * @see http://tools.ietf.org/html/rfc6749#section-1.5
     * @var string
     */
    protected $refreshToken;

    /**
     * @see http://openid.net/specs/openid-connect-core-1_0.html#IDToken
     * @var array
     */
    protected $idToken;

    /**
     * @var string
     */
    protected $rawIdToken;

    /**
     * @see http://tools.ietf.org/html/rfc6749#section-4.2.2
     * @see http://tools.ietf.org/html/rfc6749#appendix-A.14
     * @var integer
     */
    private $expiresIn;

    /**
     * @var integer
     */
    private $createdAt;

    /**
     * @param string $idToken The OpenId Connect ID Token
     * @param string|array $accessToken The OAuth access token
     * @param array $roles Roles for the token
     */
    public function __construct($idToken, $accessToken, array $roles = array())
    {
        parent::__construct($roles);

        $this->setRawAccessToken($accessToken);
        $this->setRawIdToken($idToken);

        parent::setAuthenticated(count($roles) > 0);
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * @param string $accessToken The OAuth access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param array $idToken The OpenId Connect ID Token
     */
    public function setIdToken($idToken)
    {
        $this->idToken = $idToken;
    }

    /**
     * @return array The OpenId Connect ID Token
     */
    public function getIdToken()
    {
        return $this->idToken;
    }

    /**
     * @param array|string $token The OAuth token
     */
    public function setRawAccessToken($token)
    {
        if (is_array($token)) {
            if (isset($token['access_token'])) {
                $this->accessToken = $token['access_token'];
            }

            if (isset($token['refresh_token'])) {
                $this->refreshToken = $token['refresh_token'];
            }

            if (isset($token['expires_in'])) {
                $this->setExpiresIn($token['expires_in']);
            }


            $this->rawAccessToken = $token;
        } else {
            $this->accessToken = $token;
            $this->rawAccessToken = array('access_token' => $token);
        }
    }

    /**
     * @param array|string $token The OpenD Connect ID token
     */
    public function setRawIdToken($token)
    {
        if (is_string($token)) {

            $this->idToken = \JOSE_JWT::decode($token);
        } else {
            $this->idToken = $token;
        }
    }

    /**
     * @return array
     */
    public function getRawToken()
    {
        return $this->rawToken;
    }

    /**
     * @param string $refreshToken The OAuth refresh token
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param integer $expiresIn The duration in seconds of the access token lifetime
     */
    public function setExpiresIn($expiresIn)
    {
        $this->createdAt = time();
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return integer
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * Returns if the `access_token` is expired.
     *
     * @return boolean True if the `access_token` is expired.
     */
    public function isExpired()
    {
        if (null === $this->expiresIn) {
            return false;
        }
        return ($this->createdAt + ($this->expiresIn - time())) < 30;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->idToken,
            $this->accessToken,
            $this->rawAccessToken,
            $this->refreshToken,
            $this->expiresIn,
            $this->createdAt,
            parent::serialize()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        list(
                $this->idToken,
                $this->accessToken,
                $this->rawAccessToken,
                $this->refreshToken,
                $this->expiresIn,
                $this->createdAt,
                $parent,
                ) = $data;

        parent::unserialize($parent);
    }

}
