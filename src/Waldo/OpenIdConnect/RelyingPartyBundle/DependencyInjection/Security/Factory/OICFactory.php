<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $providerId = 'security.authentication.provider.openid_connect.' . $id;
        $this->createResourceOwnerMap($container, $id, $config);
        $container
                ->setDefinition($providerId, new DefinitionDecorator('openid_connect.security.authentication.provider'))
                ->replaceArgument(0, $userProviderId)
        ;
        
        return $providerId;
    }

    /**
     * {@inheritDoc}
     */
    protected function getListenerId()
    {
        return 'security.authentication.listener.openid_connect';
    }

    /**
     * {@inheritDoc}
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
