<?php

namespace AmorebietakoUdala\SMSServiceBundle\Providers;

use AmorebietakoUdala\SMSServiceBundle\Interfaces\SmsApiInterface;
use \Exception;

/**
 * Connection API to dinahosting.com.
 *
 * @version 1.0
 */
class SmsSarenetApi implements SmsApiInterface
{
    private const _SARENET_URL_SEND = 'https://contento-servicios.sarenet.es/srvcs/listas/api/smsssl-orange.php';

    public function __construct( 
        private string $sender, 
        private readonly string $clave, 
        private readonly string $authToken = "xxxxxxxxxx", 
        private readonly bool $test = false, 
        private readonly int $timeout = 10
    )
    {
        $this->sender = substr(str_replace(' ', '_', $sender), 0, 10);
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
     * 
     * Petition Example:
     * {
     *     "clave": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
     *     "accion": "saldo"
     * }
     * 
     * Response Examples:
     * Credit:
     * {"exito":"OK","saldo":"44"}
     * No Credit:
     * {"exito":"OK","saldo":"-1"}
     */
    public function getCredit(): float
    {
        $body = "{
            \"clave\": \"{$this->clave}\",
              \"accion\": \"saldo\"
            }";
        $response = $this->send($body);

        return $response['saldo'];
    }

    /**
     * Send the message to the telephone numbers expecified.
     *
     * @param array $numbers : Array with the recipients telephone numbers
     * @param $message : Message to be sent
     * @param string $when : The date when the message has to be sended
     *
     * Only allows 1 message per request. So we have to loop through the numbers array and send one by one.
     * Petition Example:
     * {
     *     "clave": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
     *     "remitente": "your sender",
     *     "movil": "666666666",         
     *     "texto":"message text"
     * }     
     * 
     * Response Examples:
     * Failed response:
     *   {"exito":"KO","error":"Faltan datos"}
     *
     * Success response:
     *   {"exito":"OK","id":"675e7a15-914d-08cc-f95c-xxxxxxxxxxxx"}
     * 
     * @throws \Exception
     */
    public function sendMessage(array $numbers, $message, $when = null, $customId = null)
    {
        $responses = [];
        foreach ($numbers as $number) {
            $body = "{
                \"clave\": \"{$this->clave}\",
                \"remitente\": \"{$this->sender}\",
                \"movil\": \"{$number}\",
                \"texto\":\"{$message}\"
            }";
            if (!$this->test) {
                $responses[] = array_merge($this->send($body), ['rctp_name_number' => $number]);
            } else {
                // ResposeCode and success message don't come in real responses, it's just for testing purposes
                $response = json_decode('{"exito":"OK","id":"675e7a15-914d-08cc-f95c-xxxxxxxxxxxx","responseCode":200,"message":"Success"}', true);
                $response = array_merge($response, ['rctp_name_number' => $number]);
                $responses[] = $response;
            }
        }
        //$responses['deliveryId'] = $customId;
        $responses['responseCode'] = 200;
        $responses['message'] = "Success";
        return $responses;
    }

    /**
     * Returns the history of the sended SMSs.
     * Only provides the state of the SMS.
     *
     * @param string $id : The id of the sended SMS 
     */
    public function getHistory($id)
    {
        return $this->getStatus($id);
    }

     /**
     * Returns the status of the sended SMS by id
     *
     * Petition Example:
     * {
     *     "clave": "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
     *     "accion": "estado",
     *     "id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
     * }
     * 
     * Successfull response:
     * {
     *     "exito":"OK",
     *     "id":"xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
     *     "estado":"rechazado"
     * }
     * 
     * @param string $id : The id of the sended SMS 
     */
    public function getStatus($id) {
        $body = "{
            \"clave\": \"{$this->clave}\",
              \"accion\": \"estado\",
              \"id\": \"{$id}\"
            }";
        $response = $this->send($body);

        return $response['estado'];
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
    public function send($body)
    {
        $params['auth_token'] = $this->authToken;
        $http_status = null;
        $handle = curl_init(self::_SARENET_URL_SEND);
        if (false === $handle) { // error starting curl
            throw new \Exception('0 - Couldn\'t start curl');
        } else {
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($handle, CURLOPT_SSL_VERIFYSTATUS, FALSE);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
            curl_setopt($handle, CURLOPT_HEADER, true);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',       
                'Content-Length: ' . strlen($body) ,
                'API-TOKEN-KEY:'.$this->authToken ));   

            curl_setopt($handle, CURLOPT_TIMEOUT, 60);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT,
                        $this->timeout); // set higher if you get a "28 - SSL connection timeout" error

            $response = curl_exec($handle);
            $http_status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if (201 != $http_status && 200 != $http_status) {
                throw new \Exception(curl_errno($handle).' - '.curl_error($handle).'Response:'.$response);
            } else {
                $header_size = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $header_size);
                $body = substr($response, $header_size);                
                $responseArray = json_decode($body, true);
                $responseArray['responseCode'] = $http_status; 
                if ( $responseArray['exito'] === 'OK') {
                    $responseArray['message'] = 'Success';
                } else {
                    $responseArray['message'] = 'Failed';
                }
                return $responseArray;
            }
            curl_close($handle);
        }
    }
}
