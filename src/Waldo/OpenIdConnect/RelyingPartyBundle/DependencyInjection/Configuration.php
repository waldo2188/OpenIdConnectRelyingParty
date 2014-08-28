<?php

namespace Waldo\OpenIdConnect\RelyingPartyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('waldo_oic_rp');

        $rootNode
            ->children()
                ->scalarNode('base_url')->end()
                ->scalarNode('client_id')->cannotBeEmpty()->end()
                ->scalarNode('client_secret')->cannotBeEmpty()->end()
                ->scalarNode('scope')
                    ->validate()
                        ->ifTrue(function($v) {
                            return empty($v);
                        })
                        ->thenUnset()
                    ->end()
                ->end()
            ->end()
                            
            ->children()
                ->arrayNode('endpoints_url')
                    ->isRequired()
                        ->children()
                            ->scalarNode('authorisation')
                                ->validate()
                                    ->ifTrue(function($v) {
                                        return empty($v);
                                    })
                                    ->thenUnset()
                                ->end()
                            ->end()
                            ->scalarNode('token')
                                ->validate()
                                    ->ifTrue(function($v) {
                                        return empty($v);
                                    })
                                    ->thenUnset()
                                ->end()
                            ->end()
                            ->scalarNode('userinfo')
                                ->validate()
                                    ->ifTrue(function($v) {
                                        return empty($v);
                                    })
                                    ->thenUnset()
                                ->end()
                            ->end()
                        ->end()
                ->end()
            ->end()
        ;
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }

}
