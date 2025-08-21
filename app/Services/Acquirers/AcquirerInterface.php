<?php

namespace App\Services\Acquirers;

interface AcquirerInterface
{
    public function getToken();
    public function createCharge(array $data, string $token);
}