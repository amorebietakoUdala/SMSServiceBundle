<?php

namespace AmorebietakoUdala\SMSServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sms_service');
        $rootNode->children()
            ->scalarNode('provider')->isRequired()->end()
            ->booleanNode('test')->isRequired()->end()
            ->arrayNode('providers')->isRequired()
                ->children()
                    ->arrayNode('dinahosting')
                        ->children()
                            ->scalarNode('username')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('account')->end()
                        ->end()
                    ->end()
                    ->arrayNode('acumbamail')
                        ->children()
                            ->scalarNode('authToken')->isRequired()->end()
                            ->scalarNode('sender')->isRequired()->end()
                            ->scalarNode('version')->end()
                            ->scalarNode('timeout')->end()
                            ->scalarNode('countryCode')->end()
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
