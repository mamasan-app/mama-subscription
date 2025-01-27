<?php

namespace App\DTO;

class StripeMetadata
{
    public string $id;

    public string $object;

    public int $amount;

    public int $amount_received;

    public string $currency;

    public ?string $status;

    public ?string $client_secret;

    public string $capture_method;

    public string $confirmation_method;

    public \DateTime $created;

    public bool $livemode;

    public array $payment_method_types;

    public ?string $cancellation_reason;

    public array $custom_metadata;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->object = $data['object'] ?? '';
        $this->amount = $data['amount'] ?? 0;
        $this->amount_received = $data['amount_received'] ?? 0;
        $this->currency = $data['currency'] ?? '';
        $this->status = $data['status'] ?? null;
        $this->client_secret = $data['client_secret'] ?? null;
        $this->capture_method = $data['capture_method'] ?? 'automatic';
        $this->confirmation_method = $data['confirmation_method'] ?? 'automatic';
        $this->created = isset($data['created']) ? (new \DateTime)->setTimestamp($data['created']) : new \DateTime;
        $this->livemode = $data['livemode'] ?? false;
        $this->payment_method_types = $data['payment_method_types'] ?? [];
        $this->cancellation_reason = $data['cancellation_reason'] ?? null;
        $this->custom_metadata = $data['metadata'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'amount' => $this->amount,
            'amount_received' => $this->amount_received,
            'currency' => $this->currency,
            'status' => $this->status,
            'client_secret' => $this->client_secret,
            'capture_method' => $this->capture_method,
            'confirmation_method' => $this->confirmation_method,
            'created' => $this->created->format('Y-m-d H:i:s'),
            'livemode' => $this->livemode,
            'payment_method_types' => $this->payment_method_types,
            'cancellation_reason' => $this->cancellation_reason,
            'custom_metadata' => $this->custom_metadata,
        ];
    }
}
