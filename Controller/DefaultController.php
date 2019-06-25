<?php

namespace AmorebietakoUdala\SMSServiceBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="sms_getCredit", methods={"GET"})
     */
    public function indexAction(SmsApi $sms)
    {
        $credit = $sms->getCredit();

        return $this->render('@SMSService/default/index.html.twig', [
            'credit' => $credit,
        ]);
    }
}
