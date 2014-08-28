<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * OICFactory
 *
 * @author valérian Girard <valerian.girard@educagri.fr>
 */
class OICFactory extends AbstractFactory
{

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        parent::addConfiguration($node);
        $builder = $node->children();
        $builder
                ->scalarNode('login_path')->cannotBeEmpty()->isRequired()->end()
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'security.authentication.provider.oic_rp.' . $id;
        
        $container
                ->setDefinition($providerId, new DefinitionDecorator('waldo_oic_rp.authentication.provider'))
                ->addArgument(new Reference($userProviderId))
        ;

        return $providerId;
    }

    /**
     * {@inheritDoc}
     */
    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint)
    {
        $entryPointId = 'security.authentication.entrypoint.oic_rp.' . $id;

        $container
                ->setDefinition($entryPointId, new DefinitionDecorator('waldo_oic_rp.authentication.entrypoint'))
                ->addArgument($config['login_path'])
                ->addArgument($config['use_forward'])
                ->addArgument(new Reference('waldo_oic_rp.resource_owner.generic'))
        ;

        return $entryPointId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListenerId()
    {
        return 'waldo_oic_rp.authentication.listener';
    }

    /**
     * {@inheritDoc}
     * Allow to add a custom configuration in a firewall's configuration 
     * in the security.yml file.
     */
    public function getKey()
    {
        return 'openidconnect';
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return 'http';
    }

}
