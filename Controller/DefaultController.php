<?php

namespace AmorebietakoUdala\SMSServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use AmorebietakoUdala\SMSServiceBundle\Services\SmsServiceApi;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="sms_getCredit", methods={"GET"})
     */
    public function indexAction(SmsServiceApi $smsService)
    {
        $credit = $smsService->getCredit();

        return $this->render('@SMSService/default/index.html.twig', [
            'credit' => $credit,
        ]);
    }

    private function __getSMSService($smsProvider, SmsDinaHostingApi $dinahostingService, SmsAcumbamailApi $acumbamailService)
    {
        switch ($smsProvider) {
            case 'Acumbamail':
                $this->smsService = $acumbamailService;
                break;
            case 'Dinahosting':
                $this->smsService = $dinahostingService;
                break;
        }

        return $this->smsService;
    }
}
