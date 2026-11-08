<?php

namespace EDPay\Exceptions;

use Exception;

class EDPayException extends Exception
{
    protected ?string $errorCode;
    protected ?int $httpStatus;
    protected ?array $responseBody;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?string $errorCode = null,
        ?int $httpStatus = null,
        ?array $responseBody = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
