<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

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

    
    public function createResoucerOwnerService(ContainerBuilder $container, $name, $config)
    {
        
        $definition = new DefinitionDecorator("waldo_oic_rp.abstract_resource_owner." . $name);
        $definition->setClass("%waldo_oic_rp.resource_owner.$name.class%");
        
        $container->setDefinition("waldo_oic_rp.resource_owner." . $name, $definition);
        $definition->replaceArgument(1, $config);
        
    }
}
