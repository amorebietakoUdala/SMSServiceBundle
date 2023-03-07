<?php

namespace AmorebietakoUdala\SMSServiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SmsProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $containerBuilder): void
    {
        // always first check if the primary service is defined
        if (!$containerBuilder->has(SmsServiceApi::class)) {
            return;
        }

        $definition = $containerBuilder->findDefinition(SmsServiceApi::class);

        // find all service IDs with the sms_api tag
        $taggedServices = $containerBuilder->findTaggedServiceIds('sms_api');
        foreach ($taggedServices as $id => $tags) {
            // add the transport service to the TransportChain service
            $definition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}