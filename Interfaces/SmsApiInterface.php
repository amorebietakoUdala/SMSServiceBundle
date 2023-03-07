<?php

namespace AmorebietakoUdala\SMSServiceBundle\Interfaces;

/**
 * @author ibilbao
 */
interface SmsApiInterface
{
    /**
     * Devuelve el credito disponible.
     *
     * @return float : Número de créditos (mensajes) disponibles
     *
     * @throws \Exception
     */
    public function getCredit();

    /**
     * Envia un mensaje a un numero.
     *
     * @param array $numbers : Array con los números de teléfono destino
     * @param $message : Texto del mensaje para enviar
     * @param null $when : Fecha programada para el envio
     *
     * @throws \Exception
     */
    public function sendMessage(array $numbers, $message, $when = null, $customId = null);
}
