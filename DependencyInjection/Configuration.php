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
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sms_service');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->scalarNode('provider')->isRequired()->end()
            ->booleanNode('test')->isRequired()->end()
            ->arrayNode('providers')->isRequired()
                ->children()
                    ->arrayNode('dinahosting')
                        ->children()
                            ->scalarNode('username')->isRequired()->end()
                            ->scalarNode('password')->isRequired()->end()
                            ->scalarNode('account')->isRequired()->end()
                            ->scalarNode('sender')->end()
                        ->end()
                    ->end()
                    ->arrayNode('acumbamail')
                        ->children()
                            ->scalarNode('sender')->isRequired()->end()
                            ->scalarNode('authToken')->isRequired()->end()
                            ->scalarNode('version')->end()
                            ->scalarNode('timeout')->end()
                            ->scalarNode('countryCode')->end()
                        ->end()
                    ->end()
                    ->arrayNode('smspubli')
                        ->children()
                            ->scalarNode('sender')->isRequired()->end()
                            ->scalarNode('unitaryCost')->isRequired()->end()
                            ->scalarNode('subAccountName')->defaultValue(null)->end()
                            ->scalarNode('api_key')->isRequired()->end()
                            ->scalarNode('version')->end()
                            ->scalarNode('timeout')->defaultValue(60)->end()
                            ->scalarNode('countryCode')->defaultValue(34)->end()
                            ->scalarNode('confirmationRouteName')->end()
                            ->scalarNode('domainUrl')->end()
                        ->end()
                    ->end()
                    ->arrayNode('sarenet')
                        ->children()
                            ->scalarNode('sender')->isRequired()->end()
                            ->scalarNode('clave')->end()
                            ->scalarNode('authToken')->isRequired()->end()
                            ->scalarNode('timeout')->defaultValue(60)->end()
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
