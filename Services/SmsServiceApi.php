<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AmorebietakoUdala\SMSServiceBundle\Services;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsDinaHostingApi;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsAcumbamailApi;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsPubliApi;
use AmorebietakoUdala\SMSServiceBundle\Providers\SmsSarenetApi;

/**
 * Description of SmsServiceApi.
 *
 * @author ibilbao
 */
class SmsServiceApi implements SmsApiInterface
{
    private string $provider = '';

    private $smsService = null;

    public function __construct($provider, SmsDinaHostingApi $smsDinaHostingApi, SmsAcumbamailApi $smsAcumbamailApi, SmsPubliApi $smsPubliApi, SmsSarenetApi $sarenetApi)
    {
        switch ($provider) {
            case 'Acumbamail':
                $this->smsService = $smsAcumbamailApi;
                break;
            case 'Dinahosting':
                $this->smsService = $smsDinaHostingApi;
                break;
            case 'Smspubli':
                $this->smsService = $smsPubliApi;
                break;
            case 'Sarenet':
                $this->smsService = $sarenetApi;
                break;
            }
        $this->provider = $provider;
        return $this->smsService;
    }

    public function getCredit(): float
    {
        //dump($this->smsService, $this->provider);
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

    public function sendMessage(array $numbers, $message, $when = null, $customId = null)
    {
        return $this->smsService->sendMessage($numbers, $message, $when, $customId);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getSender(): string
    {
        return $this->smsService->getSender();
    }

    public function getProviderService() {
        return $this->smsService ? get_class($this->smsService) : null;
    }
}
