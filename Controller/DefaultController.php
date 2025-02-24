<?php

namespace AmorebietakoUdala\SMSServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use AmorebietakoUdala\SMSServiceBundle\Services\SmsServiceApi;

class DefaultController extends AbstractController
{
    #[Route('/credit', name: 'sms_getCredit', methods: ['GET'])]
    public function index(SmsServiceApi $smsService)
    {
        $credit = $smsService->getCredit();

        return $this->render('@SMSService/default/index.html.twig', [
            'credit' => $credit,
            'provider' => $smsService->getProvider(),
            'providerService' => $smsService->getProviderService(),
        ]);
    }
}
