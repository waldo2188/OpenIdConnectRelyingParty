<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WaldoOpenIdConnectRelyingPartyExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('openid_connect.xml');
        $loader->load('buzz.xml');
     
        $this->constructEndpointUrl($config);
        
        // setup buzz client settings
        $httpClient = $container->getDefinition('buzz.client');
        $httpClient->addMethodCall('setVerifyPeer', array($config['http_client']['verify_peer']));
        $httpClient->addMethodCall('setTimeout', array($config['http_client']['timeout']));
        $httpClient->addMethodCall('setMaxRedirects', array($config['http_client']['max_redirects']));
        $httpClient->addMethodCall('setIgnoreErrors', array($config['http_client']['ignore_errors']));
        if (isset($config['http_client']['proxy']) && $config['http_client']['proxy'] != '') {
            $httpClient->addMethodCall('setProxy', array($config['http_client']['proxy']));
        }
        $container->setDefinition('waldo_oic_rp.http_client', $httpClient);
        
        
        $jwkHandler = $container->getDefinition('waldo_oic_rp.jwk_handler');
        $jwkHandler->replaceArgument(0, $config['jwk_url']);
        $jwkHandler->replaceArgument(1, $config['jwk_cache_ttl']);
        
        
        $container->getDefinition('waldo_oic_rp.validator.id_token')
            ->replaceArgument(0, $config);
        
        $container->getDefinition('waldo_oic_rp.http_client_response_handler')
            ->replaceArgument(1, $config);
        
        $name = 'generic';
        $this->createResoucerOwnerService($container, $name, $config);
                    
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'waldo_oic_rp';
    }

    /**
     * Add issuer URL to the begining of each endpoint url
     * @param array $config
     */
    public function constructEndpointUrl(&$config)
    {
        foreach($config['endpoints_url'] as $key => $endpoint) {
            $config['endpoints_url'][$key] = $config['issuer'] . $endpoint;
            
        }
    }


    public function createResoucerOwnerService(ContainerBuilder $container, $name, $config)
    {
        $definition = new DefinitionDecorator("waldo_oic_rp.abstract_resource_owner." . $name);
        $definition->setClass("%waldo_oic_rp.resource_owner.$name.class%");
        
        $container->setDefinition("waldo_oic_rp.resource_owner." . $name, $definition);
        $definition->replaceArgument(5, $config);
        
    }
}
