<?php

namespace AmorebietakoUdala\SMSServiceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/*
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */

class SMSServiceExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $dinaHostingDefinition = $container->getDefinition('AmorebietakoUdala\SMSServiceBundle\Providers\SmsDinaHostingApi');
        $dinaHostingDefinition->setArgument(0, $config['providers']['dinahosting']['username']);
        $dinaHostingDefinition->setArgument(1, $config['providers']['dinahosting']['password']);
        $dinaHostingDefinition->setArgument(2, $config['providers']['dinahosting']['account']);
        $dinaHostingDefinition->setArgument(3, $config['test']);
        $dinaHostingDefinition->setArgument(4, $config['providers']['dinahosting']['sender']);
        $acumbamailDefinition = $container->getDefinition('AmorebietakoUdala\SMSServiceBundle\Providers\SmsAcumbamailApi');
        $acumbamailDefinition->setArgument(0, $config['providers']['acumbamail']['authToken']);
        $acumbamailDefinition->setArgument(1, $config['test']);
        $acumbamailDefinition->setArgument(2, $config['providers']['acumbamail']['sender']);
        $acumbamailDefinition->setArgument(3, $config['providers']['acumbamail']['version']);
        $acumbamailDefinition->setArgument(4, $config['providers']['acumbamail']['timeout']);
        $acumbamailDefinition->setArgument(5, $config['providers']['acumbamail']['countryCode']);
        $smsServiceDefinition = $container->getDefinition('AmorebietakoUdala\SMSServiceBundle\Services\SmsServiceApi');
        $smsServiceDefinition->setArgument(0, $config['provider']);
        $smsServiceDefinition->setArgument(1, $dinaHostingDefinition);
        $smsServiceDefinition->setArgument(2, $acumbamailDefinition);
    }
}
