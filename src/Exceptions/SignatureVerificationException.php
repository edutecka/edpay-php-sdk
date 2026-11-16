<?php

namespace EDPay\Exceptions;

class SignatureVerificationException extends EDPayException
{
    public function __construct(string $message = "Webhook signature is invalid or missing.", int $code = 401)
    {
        parent::__construct($message, $code, "invalid_webhook_signature", 401);
    }
}
