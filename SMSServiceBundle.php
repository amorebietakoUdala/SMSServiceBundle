<?php

namespace AmorebietakoUdala\SMSServiceBundle;

use AmorebietakoUdala\SMSServiceBundle\DependencyInjection\SMSServiceExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SMSServiceBundle extends Bundle
{
     /**
     * Overridden to allow for the custom extension alias.
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new SMSServiceExtension();
        }
        return $this->extension;
    }
}
