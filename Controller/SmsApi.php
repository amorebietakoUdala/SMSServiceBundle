<?php

namespace AmorebietakoUdala\SMSServiceBundle\Controller;

/**
 * Se encarga del envio de SMS usando la API de dinahosting.com.
 *
 * @version 1.0
 */
class SmsApi
{
    private const _DINAHOSTING_URL_SEND = 'https://dinahosting.com/special/api.php';
    /**
     * @var
     */
    private $username;

    /**
     * @var
     */
    private $password;

    /**
     * @var
     */
    private $account;

    /**
     * @var
     */
    private $test;

    public function __construct($username = null, $password = null, $account = null, $test = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->account = $account;
        $this->test = $test;
    }

    /**
     * Devuelve el credito disponible.
     *
     * @return int : Número de créditos (mensajes) disponibles
     *
     * @throws Exception
     */
    public function getCredit()
    {
        $params = ['account' => $this->account, 'command' => 'Sms_GetCredit'];
        $response = $this->send($params);

        return intval($response->data);
    }

    /**
     * Envia un mensaje a un numero.
     *
     * @param array $numbers : Array con los números de teléfono destino
     * @param $message : Texto del mensaje para enviar
     * @param null $when : Fecha programada para el envio
     *
     * @throws Exception
     */
    public function sendMessage(array $numbers, $message, $when = null)
    {
        $params = [
            'account' => $this->account,
            'contents' => $message,
            'to' => $numbers,
            'from' => $this->account,
            'command' => 'Sms_Send_Bulk_Limited_Unicode',
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
            return json_decode('{"trId": "dh5d0363af7c2744.81726264","responseCode": 1000,"message": "Success.","data": true,"command": "Sms_Send_Bulk_Limited_Unicode"}');
        }
    }

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
     * Realiza la petición remota.
     *
     * @param $params : Array asociativo con los nombres de los parametros y sus valores
     *
     * @return bool|mixed|string : El resultado de la petición
     *
     * @throws Exception
     */
    public function send($params)
    {
        $params['responseType'] = 'Json';

        $args = http_build_query($params, '', '&');
        $headers = array();

        $handle = curl_init(self::_DINAHOSTING_URL_SEND);
        if (false === $handle) { // error starting curl
            throw new Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_URL, self::_DINAHOSTING_URL_SEND);

            curl_setopt($handle, CURLOPT_USERPWD, $this->username.':'.$this->password);
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        4); // set higher if you get a "28 - SSL connection timeout" error

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
                throw new Exception(curl_errno($handle).' - '.curl_error($handle));
            }

            curl_close($handle);
        }
        $response = json_decode($response);
        if (1000 != $response->responseCode) {
            throw new \Exception(json_encode($response->errors));
        }

        return $response;
    }
}
