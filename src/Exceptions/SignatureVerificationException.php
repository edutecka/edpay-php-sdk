<?php

namespace EDPay\Exceptions;

class SignatureVerificationException extends EDPayException
{
    public function __construct(string $message = "Assinatura do webhook inválida ou ausente.", int $code = 401)
    {
        parent::__construct($message, $code, "invalid_webhook_signature", 401);
    }
}
