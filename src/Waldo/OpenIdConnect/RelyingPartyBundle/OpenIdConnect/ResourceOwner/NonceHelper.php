<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\Security\Core\Exception\InvalidNonceException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Nonce
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class NonceHelper
{

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * this method generate a nonce/state value, store it in a session and return
     * the string to put in http request.
     * 
     * @param type $uniqueValue
     * @param type $type
     * @return string
     */
    public function buildNonceValue($uniqueValue, $type = "nonce")
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
    public function checkStateAndNonce(Request $request)
    {
        foreach (array("state", "nonce") as $type) {
            if ($request->query->has($type)) {

                if (!$this->isNonceValid($type, $request->query->get($type))) {

                    throw new InvalidNonceException(
                    sprintf("the %s value is not the one expected", $type)
                    );
                }
            } else {
                $this->session->remove("auth.oic." . $type);
            }
        }
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
    public function generateNonce($uniqueValue)
    {
        $size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB);
        $hash = bin2hex(mcrypt_create_iv($size, MCRYPT_DEV_URANDOM));
        $nonce = sprintf("%s-%s", $hash, \JOSE_URLSafeBase64::encode($uniqueValue));
        $nonceEnc = \JOSE_URLSafeBase64::encode($nonce);

        if (strlen($nonceEnc) > 255) {
            $nonceEnc = substr($nonceEnc, 0, 254);
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
    public function isNonceValid($type, $responseNonce)
    {
        $referenceNonce = unserialize($this->session->get("auth.oic." . $type));
        $this->session->remove("auth.oic." . $type);

        if ($referenceNonce === $responseNonce) {
            return true;
        }

        throw new InvalidNonceException("Nonce value is corrupted");
    }

}
