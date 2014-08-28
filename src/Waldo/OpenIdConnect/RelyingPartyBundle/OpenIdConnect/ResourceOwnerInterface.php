<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect;

use Symfony\Component\HttpFoundation\Request;

/**
 * ResourceOwnerInterface
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
interface ResourceOwnerInterface
{
    /**
     * Returns the provider's authorization url
     *
     * @param string $redirectUri The uri to redirect the client back to
     * @param array $extraParameters An array of parameters to add to the url
     *
     * @return string The authorization url
     */
    public function getAuthenticationEndpointUrl(Request $request, $redirectUri, array $extraParameters = array());

    /**
     * Return a name for the resource owner.
     *
     * @return string
     */
    public function getName();

}
