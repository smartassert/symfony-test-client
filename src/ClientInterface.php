<?php

declare(strict_types=1);

namespace SmartAssert\SymfonyTestClient;

use Psr\Http\Message\ResponseInterface as Response;

interface ClientInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function makeRequest(string $method, string $uri, array $headers = [], ?string $body = null): Response;
}
