<?php

namespace AmorebietakoUdala\SMSServiceBundle\Providers;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;
use GuzzleHttp\Client;

/**
 * Se encarga del envio de SMS usando la API de acumbamail.com.
 *
 * @version 1.0
 */
class SmsAcumbamailApi implements SmsApiInterface
{
    private const _ACUMBAMAIL_URL_SEND = 'https://acumbamail.com/';

    /**
     * @var : Token de acumbamail de tu usuario
     */
    private $authToken;

    /**
     * @var : Versión de API de acumbamail
     */
    private $version;

    /**
     * @var : Si se pone a true simula la respuesta correcta, pero no envía
     */
    private $test;

    /**
     * @var : Texto que aparece como enviante del SMS
     */
    private $sender;

    /**
     * @var : Timeout de conexión al API
     */
    private $timeout;

    /**
     * @var : Código de país para los teléfonos
     */
    private $countryCode;

    /**
     * @var : Client
     */
    private $client;

    public function __construct($authToken = null, $test = false, $sender, $version = 1, $timeout = 5.0, $countryCode = '34')
    {
        $this->authToken = $authToken;
        $this->test = $test;
        $this->version = $version;
        $this->client = new Client(['base_uri' => self::_ACUMBAMAIL_URL_SEND.'api/'.$this->version.'/']);
        $this->timeout = $timeout;
        $this->sender = substr(str_replace(' ', '_', $sender), 0, 10);
        $this->countryCode = $countryCode;
    }

    /**
     * Devuelve el credito disponible.
     *
     * @return int : Número de créditos (mensajes) disponibles
     *
     * @throws \Exception
     */
    public function getCredit()
    {
        $operation = 'getCreditsSMS';
        $response = $this->client->request('POST', $operation.'/?auth_token='.$this->authToken);
        $json = json_decode($response->getBody()->getContents(), true);

        return $json['Creditos'];
    }

    /**
     * Envia un mensaje a un numero.
     *
     * @param array $numbers : Array con los números de teléfono destino
     * @param $message : Texto del mensaje para enviar
     * @param null $when : Fecha programada para el envio
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
            'auth_token' => $this->authToken,
        ];

        if (!$this->test) {
            $response = $this->send($operation, $params);
        } else {
            $response = json_decode('{"messages": [{"status": 0, "credits": 1, "id": 2889449}]}', true);
            $response['responseCode'] = '201';
            $response['message'] = 'Test Success';
        }

        return $response;
    }

    public function getHistory($start = 0, $end = 100)
    {
    }

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
     * Realiza la petición remota.
     *
     *  @param $operation : API operation to send
     *  @param $params : Array asociativo con los nombres de los parametros y sus valores
     *
     * @return bool|mixed|string : El resultado de la petición
     *
     * @throws \Exception
     */
    public function send($operation, $params)
    {
        $query = '';
        foreach ($params as $key => $value) {
            $query = '&'.$key.'='.$value.$query;
        }
        $query = substr($query, 1);
        $http_status = null;
        $handle = curl_init(self::_ACUMBAMAIL_URL_SEND);
        if (false === $handle) { // error starting curl
            throw new \Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_URL, self::_ACUMBAMAIL_URL_SEND.'api/'.$this->version.'/'.$operation.'/?'.$query);

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        10); // set higher if you get a "28 - SSL connection timeout" error

            $curlversion = curl_version();
            curl_setopt($handle, CURLOPT_USERAGENT, 'PHP '.phpversion().' + Curl '.$curlversion['version']);
            curl_setopt($handle, CURLOPT_REFERER, null);

            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER,
                        false); // set false if you get a "60 - SSL certificate problem" error

            curl_setopt($handle, CURLOPT_POST, true);

            $response = curl_exec($handle);

            if (!$response) {
                throw new \Exception(curl_errno($handle).' - '.curl_error($handle));
            }

            $http_status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);
        }

        if (201 != $http_status) {
            throw new \Exception($response);
        }
        $response = json_decode($response, true);
        $response['responseCode'] = $http_status;
        $response['message'] = 'Success';

        return $response;
    }
}
