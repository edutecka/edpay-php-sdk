<?php

namespace EDPay\Laravel;

use EDPay\LocalPaymentStore;
use Illuminate\Support\Facades\DB;

class DatabasePaymentStore implements LocalPaymentStore
{
    public function findByReference(string $reference): ?array
    {
        $row = DB::table('edpay_payments')->where('reference', $reference)->first();
        return $row ? (array) $row : null;
    }

    public function save(array $payment): void
    {
        $data = $payment['data'] ?? $payment;
        if (isset($data['payment']) && is_array($data['payment'])) $data = $data['payment'];
        $id = $data['id'] ?? null;
        $reference = $data['reference'] ?? null;
        $amountMinor = $data['amount_minor'] ?? null;
        if (!is_string($id) || $id === '' || !is_string($reference) || $reference === '' || !is_numeric($amountMinor)) return;
        $record = [
            'id' => $id,
            'reference' => $reference,
            'trans_id' => $data['trans_id'] ?? null,
            'amount_minor' => is_numeric($amountMinor) ? (int) $amountMinor : null,
            'currency' => $data['currency'] ?? 'MZN',
            'fee_minor' => $data['platform_fee_minor'] ?? null,
            'net_amount_minor' => $data['receiver_net_minor'] ?? null,
            'method' => $data['method'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'customer_id' => $data['customer_id'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'raw_response' => json_encode($payment),
            'completed_at' => $data['completed_at'] ?? null,
            'updated_at' => now(),
        ];
        DB::table('edpay_payments')->updateOrInsert(['id' => $id], $record + ['created_at' => now()]);
    }
}
