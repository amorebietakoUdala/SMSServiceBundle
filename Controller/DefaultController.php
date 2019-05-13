<?php

namespace SMSService\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="sms_getCredit", methods={"GET"})
     */
    public function indexAction(SmsSender $sms)
    {
        $credit = $sms->getCredit();

        return $this->render('@SMSService/default/index.html.twig', [
            'credit' => $credit,
        ]);
    }
}
