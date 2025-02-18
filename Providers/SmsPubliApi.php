<?php

namespace AmorebietakoUdala\SMSServiceBundle\Providers;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Exception;

/**
 * Connection API to smspubli.com.
 *
 * @version 1.0
 */
class SmsPubliApi implements SmsApiInterface
{
    # https://api.gateway360.com/api/3.0/account/get-balance
    private const _SMSPUBLI_URL_SEND = 'https://api.gateway360.com/';

    /**
     * @var : SmsPubli user token
     */
    private $apiKey;

    /**
     * @var : SmsPubli API version
     */
    private string $version;

    /**
     * @var boolean: To Simulate the API response without making it set it to true
     */
    private $test;

    /**
     * @var string: Text especifying the sender of SMS. Can't have spaces.
     *              Only 11 characters maximum.
     */
    private $sender;

    /**
     * @var int: Maximum time in seconds that you allow the connection phase
     */
    private $timeout;

    /**
     * @var string: Country code to add to the telephones without +
     */
    private $countryCode;

    /**
     * @var string: Username of the Sub Account Name
     */
    private $subAccountName;

    /**
     * @var float: Unitary price per SMS to calculate balance
     */
    private $unitaryCost;

    /**
     * @var string: Confirmation endpoint for sent SMSs 
     */
    private $confirmationRouteName = null;

    /**
     * @var string: Domain URL without final '/'
     */
    private $domainUrl = null;

    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router, $sender, $unitaryCost, 
                                $subAccountName = null, $apiKey = null, $test = false, $version = 1, $timeout = 10, $countryCode = '34', 
                                $confirmationRouteName = null, $domainUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->test = $test;
        $this->version = $version;
        $this->timeout = $timeout;
        $this->sender = substr(str_replace(' ', '_', $sender), 0, 10);
        $this->countryCode = $countryCode;
        $this->subAccountName = $subAccountName;
        $this->unitaryCost = $unitaryCost;
        $this->confirmationRouteName = $confirmationRouteName;
        $this->domainUrl = $domainUrl;
        $this->router = $router;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * Returns the credit avaible.
     *
     * @return float : Number of available credits (messages)
     *
     * @throws \Exception
     */
    public function getCredit(): float
    {
        $operation = 'account/get-balance';
        $params = [];
        if ($this->subAccountName !== null ) {
            $params = [
                'user_name' => $this->subAccountName,
            ];
        }
        $response = $this->send($operation,$params);
        $balance = $response['result']['balance'];
        $currency = $response['result']['currency'];
        if ($currency === "EUR") {
            $balance = round(floatVal($balance) / $this->unitaryCost);    
        }

        return $balance;
    }

    /**
     * Send the message to the telephone numbers expecified.
     * https://panel.smspubli.com/api/3.0/docs/sms/send
     *
     * @param array $numbers : Array with the recipients telephone numbers
     * @param $message : Message to be sent
     * @param string $when : The date when the message has to be sended
     *
     * @throws \Exception
     */
    public function sendMessage(array $numbers, $message, $when = null, $customId = null)
    {
        if (count($numbers) > 1000) {
            throw new \Exception("1000 SMS limit exceeded");
        }
        $operation = 'sms/send';
        $formatedTelephones = $this->formatTelephones($numbers);
        $messages = $this->createMessages($formatedTelephones, $message, $customId);
        $messagesJson = $messages;
        $params = [
            'messages' => $messagesJson,
        ];
        if ($this->test) {
            // If this parameter is sent, SMSPubli responses normally but it's doesn't sent anything and it has no cost. Intended for testing an debugging.
            $params['fake'] = 1;
            // Succesfull Response example
            // {"status":"ok","result":[{"status":"ok","sms_id":"a599b0d11d084903a5fefbf5dbdf43f9","custom":"1739865151174"}],"responseCode":200,"message":"Success"}
            // return json_decode('{"status":"ok","result":[{"status":"ok","sms_id":"a599b0d11d084903a5fefbf5dbdf43f9","custom":"1739865151174"}],"responseCode":200,"message":"Success"}', true);
        }
        if ($this->confirmationRouteName !== null ) {
            if ( $this->domainUrl === null ) {
                $params['report_url'] = $this->router->generate($this->confirmationRouteName,[], UrlGeneratorInterface::ABSOLUTE_URL);
            } else {
                $params['report_url'] = $this->domainUrl . $this->router->generate($this->confirmationRouteName);
            }
        }
        $response = $this->send($operation, $params);
        return $response;
    }

    /**
     * Returns the history of the sended SMSs.
     *
     * @param int $start: Especifies the starting record
     * @param int $end:   Especifies the ending record
     */
    public function getHistory(\DateTime $start_date, \DateTime $end_date)
    {
        // https://acumbamail.com/api/1/getSMSQuickSubscriberReport/?auth_token=<authToken>&start_date=2019-11-14 08:00&end_date=2019-11-14 10:00
        $operation = 'getSMSQuickSubscriberReport';
        $params = [
            'start_date' => $start_date->format('Y-m-d H:i'),
            'end_date' => $end_date->format('Y-m-d H:i'),
        ];
        $response = $this->send($operation, $params);

        return $response;
    }

    /**
     * Changes the telephones to international format.
     *
     * @param array $numbers : Array with the recipients telephone numbers
     *
     * @return array
     *
     * @throws Exception
     */
    private function formatTelephones(array $numbers)
    {
        $formatedTelephones = [];
        foreach ($numbers as $number) {
            if ('' === trim($number)) {
                throw new Exception('The are empty telephones');
            }
            if ('+' === substr($number, 0, 0)) {
                $formatedTelephones[] = substr($number, 1);
            } else {
                $formatedTelephones[] = $this->countryCode.$number;
            }
        }
        return $formatedTelephones;
    }

    /**
     * Creates the message format for API to work.
     *
     * @param array  $numbers : Array with the recipients telephone numbers
     * @param string $message : The message to be sent
     *
     * @return array
     *
     * @throws Exception
     */
    private function createMessages($numbers, $message, $customId)
    {
        $messages = [];
        foreach ($numbers as $number) {
            if ('' === trim($number)) {
                throw new Exception('The are empty telephones');
            }
            $messages[] = [
                'from' => $this->sender,
                'to' => $number,
                'text' => $message,
                'custom' => ''.$customId,
            ];
        }

        return $messages;
    }

    /**
     * Sends the request to the server.
     *
     * @param $params : Array asociativo con los nombres de los parametros y sus valores
     *
     * @return bool|mixed|string : The result of the request
     *
     * @throws \Exception
     */
    public function send($operation, $params = null)
    {
        $params['api_key'] = $this->apiKey;
        $http_status = null;
        $handle = curl_init(self::_SMSPUBLI_URL_SEND);
        $headers = array('Content-Type: application/json');          
        if (false === $handle) { // error starting curl
            throw new \Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_URL, self::_SMSPUBLI_URL_SEND.'api/'.$this->version.'/'.$operation);

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        $this->timeout); // set higher if you get a "28 - SSL connection timeout" error

            $curlversion = curl_version();
            curl_setopt($handle, CURLOPT_USERAGENT, 'PHP '.phpversion().' + Curl '.$curlversion['version']);
            curl_setopt($handle, CURLOPT_REFERER, null);

            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER,
                        false); // set false if you get a "60 - SSL certificate problem" error

            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($params));
            
            $response = curl_exec($handle);

            $http_status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if (201 != $http_status && 200 != $http_status) {
                # Error Response example
                # {"status":"error","error_id":"JSON_PARSE_ERROR","error_msg":"Your JSON was formatted incorrectly."}
                # {"status":"error","error_id":"BAD_PARAMS","error_msg":"Parameter `messages` must be an array."}
                # { "status": "ok", "result": [{ "status": "ok", "sms_id": "b7807936645e499caca19f065f65fd63", "custom": "" }] }
                throw new \Exception(curl_errno($handle).' - '.curl_error($handle).'Response:'.$response);
            } else {
                # {"status": "ok","result": {"balance": "0.6000", "currency": "EUR"}}
                # 
                $response = json_decode($response, true);
                $response['responseCode'] = $http_status;
                if ($response['status'] === 'ok') {
                    $response['message'] = 'Success';
                }

                return $response;
            }
            curl_close($handle);
        }
    }
}
