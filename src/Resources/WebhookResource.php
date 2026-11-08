<?php

namespace EDPay\Resources;

use EDPay\Exceptions\SignatureVerificationException;

class WebhookResource
{
    /**
     * Valida a assinatura HMAC-SHA256 do webhook e devolve o evento decodificado.
     *
     * @throws SignatureVerificationException Se a assinatura não coincidir
     */
    public function constructEvent(string $payload, string $signatureHeader, string $secret): array
    {
        if (empty($signatureHeader) || empty($secret)) {
            throw new SignatureVerificationException("Assinatura ou chave secreta ausentes.");
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signatureHeader)) {
            throw new SignatureVerificationException("Assinatura do webhook inválida.");
        }

        $event = json_decode($payload, true);
        if (!is_array($event) || !isset($event['event'])) {
            throw new SignatureVerificationException("Payload JSON de webhook inválido.");
        }

        return $event;
    }
}
