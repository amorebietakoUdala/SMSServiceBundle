<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AmorebietakoUdala\SMSServiceBundle\Services;

use AmorebietakoUdala\SMSServiceBundle\Providers\SmsDinaHostingApi;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsAcumbamailApi;
use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;

/**
 * Description of SmsServiceApi.
 *
 * @author ibilbao
 */
class SmsServiceApi implements SmsApiInterface
{
    private $provider = null;

    private $smsService = null;

    public function __construct($provider, SmsDinaHostingApi $smsDinaHostingApi, SmsAcumbamailApi $smsAcumbamailApi)
    {
        switch ($provider) {
            case 'Acumbamail':
                $this->smsService = $smsAcumbamailApi;
                break;
            case 'Dinahosting':
                $this->smsService = $smsDinaHostingApi;
                break;
        }
        $this->provider = $provider;

        return $this->smsService;
    }

    public function getCredit(): int
    {
        return $this->smsService->getCredit();
    }

    public function getHistory($start_date = null, $end_date = null)
    {
        if (null === $start_date) {
            // Since today at 00:00
            $start_date = new \DateTime((new \DateTime())->format('Y-m-d'));
        }
        if (null === $end_date) {
            $end_date = new \DateTime();
        }

        return $this->smsService->getHistory($start_date, $end_date);
    }

    public function sendMessage(array $numbers, $message, $when = null)
    {
        return $this->smsService->sendMessage($numbers, $message, $when);
    }
}
