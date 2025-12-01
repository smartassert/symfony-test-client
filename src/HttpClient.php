<?php

declare(strict_types=1);

namespace SmartAssert\SymfonyTestClient;

use Psr\Http\Client\ClientExceptionInterface as HttpClientException;
use Psr\Http\Client\ClientInterface as Psr7HttpClient;
use Psr\Http\Message\RequestFactoryInterface as RequestFactory;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamFactoryInterface as StreamFactory;

readonly class HttpClient implements ClientInterface
{
    public function __construct(
        private RequestFactory $requestFactory,
        private StreamFactory $streamFactory,
        private Psr7HttpClient $httpClient,
    ) {}

    /**
     * @param array<string, string> $headers
     *
     * @throws HttpClientException
     */
    public function makeRequest(string $method, string $uri, array $headers = [], ?string $body = null): Response
    {
        return $this->httpClient->sendRequest($this->createRequest($method, $uri, $headers, $body));
    }

    /**
     * @param array<string, string> $headers
     */
    private function createRequest(string $method, string $uri, array $headers = [], ?string $body = null): Request
    {
        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        if (is_string($body)) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        return $request;
    }
}
