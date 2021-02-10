<?php

namespace AmorebietakoUdala\SMSServiceBundle\Providers;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;

/**
 * Connection API to dinahosting.com.
 *
 * @version 1.0
 */
class SmsAcumbamailApi implements SmsApiInterface
{
    private const _ACUMBAMAIL_URL_SEND = 'https://acumbamail.com/';

    /**
     * @var : Acumbamail user token
     */
    private $authToken;

    /**
     * @var : Acumbamail API version
     */
    private $version;

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
     * @var string: Country code to add to the telephones when no starting with +
     */
    private $countryCode;

    public function __construct($authToken = null, $test = false, $sender, $version = 1, $timeout = 10, $countryCode = '34')
    {
        $this->authToken = $authToken;
        $this->test = $test;
        $this->version = $version;
        $this->timeout = $timeout;
        $this->sender = substr(str_replace(' ', '_', $sender), 0, 10);
        $this->countryCode = $countryCode;
    }

    /**
     * Returns the credit avaible.
     *
     * @return int : Number of available credits (messages)
     *
     * @throws \Exception
     */
    public function getCredit()
    {
        $operation = 'getCreditsSMS';
        $response = $this->send($operation);

        return $response['Creditos'];
    }

    /**
     * Send the message to the telephone numbers expecified.
     *
     * @param array $numbers : Array with the recipients telephone numbers
     * @param $message : Message to be sent
     * @param string $when : The date when the message has to be sended
     *
     * @throws \Exception
     */
    public function sendMessage(array $numbers, $message, $when = null)
    {
        $operation = 'sendSMS';
        $formatedTelephones = $this->__formatTelephones($numbers);
        $messages = $this->__createMessages($formatedTelephones, $message);

        $messagesJson = json_encode($messages);

        $params = [
            'messages' => $messagesJson,
        ];

        if (!$this->test) {
            $response = $this->send($operation, $params);
        } else {
            $response = json_decode('{"messages": [{"status": 0, "credits": 1, "id": 2889449}]}', true);
            $response['responseCode'] = '201';
            $response['message'] = 'Success';
        }

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
    private function __formatTelephones(array $numbers)
    {
        $formatedTelephones = [];
        foreach ($numbers as $number) {
            if ('' === trim($number)) {
                throw new Exception('The are empty telephones');
            }
            if ('+' === substr($number, 0, 0)) {
                $formatedTelephones[] = $number;
            } else {
                $formatedTelephones[] = '+'.$this->countryCode.$number;
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
    private function __createMessages($numbers, $message)
    {
        $messages = [];
        foreach ($numbers as $number) {
            if ('' === trim($number)) {
                throw new Exception('The are empty telephones');
            }
            $messages[] = [
                'recipient' => $number,
                'body' => $message,
                'sender' => $this->sender,
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
        $params['auth_token'] = $this->authToken;
        $http_status = null;
        $handle = curl_init(self::_ACUMBAMAIL_URL_SEND);
        if (false === $handle) { // error starting curl
            throw new \Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            curl_setopt($handle, CURLOPT_URL, self::_ACUMBAMAIL_URL_SEND.'api/'.$this->version.'/'.$operation.'/');

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        $this->timeout); // set higher if you get a "28 - SSL connection timeout" error

            $curlversion = curl_version();
            curl_setopt($handle, CURLOPT_USERAGENT, 'PHP '.phpversion().' + Curl '.$curlversion['version']);
            curl_setopt($handle, CURLOPT_REFERER, null);

            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER,
                        false); // set false if you get a "60 - SSL certificate problem" error

            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $params);

            $response = curl_exec($handle);

            $http_status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if (201 != $http_status && 200 != $http_status) {
                throw new \Exception(curl_errno($handle).' - '.curl_error($handle).'Response:'.$response);
            } else {
                $response = json_decode($response, true);
                if ('sendSMS' === $operation) {
                    $response['responseCode'] = $http_status;
                    $response['message'] = 'Success';
                }

                return $response;
            }
            curl_close($handle);
        }
    }
}
