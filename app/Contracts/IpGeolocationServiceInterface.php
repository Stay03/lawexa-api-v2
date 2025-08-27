<?php

namespace App\Contracts;

interface IpGeolocationServiceInterface
{
    public function getLocation(string $ipAddress): ?array;
    
    public function isServiceAvailable(): bool;
    
    public function getProviderName(): string;
}