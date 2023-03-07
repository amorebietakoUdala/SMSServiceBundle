<?php

namespace AmorebietakoUdala\SMSServiceBundle\Providers;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;

/**
 * Connection API to dinahosting.com.
 *
 * @version 1.0
 */
class SmsDinaHostingApi implements SmsApiInterface
{
    private const _DINAHOSTING_URL_SEND = 'https://dinahosting.com/special/api.php';
    /**
     * @var String: Dinahosting username
     */
    private $username;

    /**
     * @var String: Dinahosting password
     */
    private $password;

    /**
     * @var String: Dinahosting account
     */
    private $account;

    /**
     * @var boolean: To Simulate the API response without making it set it to true
     */
    private $test;

    /**
     * @var string: Text especifying the sender of SMS. Can't have spaces.
     *              Only 11 characters maximum.
     */
    private $sender;

    public function __construct($username = null, $password = null, $account = null, $test = false, $sender = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->account = $account;
        $this->test = $test;
        if (null !== $sender) {
            $this->sender = substr(str_replace(' ', '_', $sender), 0, 10);
        } else {
            $this->sender = $this->account;
        }
    }

    /**
     * Returns the credit avaible.
     *
     * @return float : Number of available credits (messages)
     *
     * @throws \Exception
     */
    public function getCredit()
    {
        $params = ['account' => $this->account, 'command' => 'Sms_GetCredit'];
        $response = $this->send($params);

        return intval($response['data']);
    }

    /**
     * Send the message to the telephone numbers expecified.
     *
     * @param array $numbers : Array with the recipients telephone numbers
     * @param $message : Message to be sent
     * @param \DateTime $when : The date when the message has to be sended
     *
     * @throws \Exception
     */
    public function sendMessage(array $numbers, $message, $when = null, $customId = null)
    {
        $params = [
            'account' => $this->account,
            'contents' => $message,
            'to' => $numbers,
            'from' => $this->sender,
            'command' => 'Sms_Send_Bulk_Limited_Gsm7',
        ];
        if (null !== $when) {
            $when = date_format($when, 'Y-m-d H:i:s');
        }
        if (!empty($when)) {
            $params['when'] = $when;
        }

        if (!$this->test) {
            return $this->send($params);
        } else {
            return json_decode('{"trId": "dh5d0363af7c2744.81726264","responseCode": 1000,"message": "Success.","data": true,"command": "Sms_Send_Bulk_Long_Unicode"}', true);
        }
    }

    /**
     * Returns the history of the sended SMSs.
     *
     * @param int $start: Especifies the starting record
     * @param int $end:   Especifies the ending record
     */
    public function getHistory($start = 0, $end = 100)
    {
        //'https://dinahosting.com/special/api.php?AUTH_USER=username&AUTH_PWD=password&account=account&responseType=Json&start=0&end=100&command=Sms_History_GetSent'
        $params = [
            'account' => $this->account,
            'start' => $start,
            'end' => $end,
            'command' => 'Sms_History_GetSent',
        ];

        return $this->send($params);
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
    public function send($params)
    {
        $params['responseType'] = 'Json';

        $args = http_build_query($params, '', '&');
        $headers = array();

        $handle = curl_init(self::_DINAHOSTING_URL_SEND);
        if (false === $handle) { // error starting curl
            throw new \Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_URL, self::_DINAHOSTING_URL_SEND);

            curl_setopt($handle, CURLOPT_USERPWD, $this->username.':'.$this->password);
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        8); // set higher if you get a "28 - SSL connection timeout" error

            curl_setopt($handle, CURLOPT_HEADER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);

            $curlversion = curl_version();
            curl_setopt($handle, CURLOPT_USERAGENT, 'PHP '.phpversion().' + Curl '.$curlversion['version']);
            curl_setopt($handle, CURLOPT_REFERER, null);

            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER,
                        false); // set false if you get a "60 - SSL certificate problem" error

            curl_setopt($handle, CURLOPT_POSTFIELDS, $args);
            curl_setopt($handle, CURLOPT_POST, true);

            $response = curl_exec($handle);

            if ($response) {
                $response = substr($response, strpos($response, "\r\n\r\n") + 4); // remove http headers
            } else { // http response code != 200
                throw new \Exception(curl_errno($handle).' - '.curl_error($handle));
            }

            curl_close($handle);
        }
        $response = json_decode($response, true);
        if (1000 != $response['responseCode']) {
            throw new \Exception(json_encode($response['errors']));
        }

        return $response;
    }
}
