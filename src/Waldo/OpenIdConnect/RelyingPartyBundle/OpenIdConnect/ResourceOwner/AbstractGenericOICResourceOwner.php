<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwner;

use Waldo\OpenIdConnect\RelyingPartyBundle\OpenIdConnect\ResourceOwnerInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * GenericOICResourceOwner
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
abstract class AbstractGenericOICResourceOwner implements ResourceOwnerInterface
{

    /**
     * @var HttpUtils 
     */
    private $httpUtils;

    /**
     * @var array
     */
    private $options = array();

    function __construct(HttpUtils $httpUtils, $options)
    {
        $this->httpUtils = $httpUtils;

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
        $redirectUri = $this->httpUtils->generateUri($request, $redirectUri);
        
        echo "<pre>lol:";
        var_dump($redirectUri);
        echo "</pre>";exit;



        // authentication request
        // http://172.16.128.5/phpOp/index.php/auth?
        // state=72920ad7350b1d158e1840b18c6d7d9f
        // redirect_uri=http%3A%2F%2F172.16.128.5%2FphpRp%2Findex.php%2Fcallback
        // response_type=code
        // client_id=fmjIXIUpMoJ2nkDfHjIFXA
        // nonce=7658d3e6f5bbecf4773f2477ee50fe0d
        // scope=openid+profile+email+address
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
            'authorisation_endpoint_url',
            'token_endpoint_url',
            'userinfo_endpoint_url',
        ));
        
        $resolver->setDefaults(array(
            'scope' => null,
        ));
        
        
    }

}
