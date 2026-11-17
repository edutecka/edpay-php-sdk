<?php

namespace EDPay;

interface LocalPaymentStore
{
    public function findByReference(string $reference): ?array;
    public function save(array $payment): void;
}
