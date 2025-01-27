<?php

namespace App\DTO;

class MiBancoMetadata
{
    public string $code;

    public string $message;

    public string $reference;

    public string $id;

    public function __construct(array $data)
    {
        $this->code = $data['code'] ?? '';
        $this->message = $data['message'] ?? '';
        $this->reference = $data['reference'] ?? '';
        $this->id = $data['id'] ?? '';
    }
}
