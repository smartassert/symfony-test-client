<?php

declare(strict_types=1);

namespace SmartAssert\SymfonyTestClient;

use Psr\Http\Message\ResponseInterface as Response;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;

class SymfonyClient implements ClientInterface
{
    private KernelBrowser $kernelBrowser;

    public function __construct(
        private readonly HttpMessageFactoryInterface $httpMessageFactory,
    ) {}

    public function setKernelBrowser(KernelBrowser $kernelBrowser): void
    {
        $this->kernelBrowser = $kernelBrowser;
    }

    public function makeRequest(string $method, string $uri, array $headers = [], ?string $body = null): Response
    {
        $headers = $this->prepareHeaders($headers);
        $parameters = $this->prepareParameters($method, $headers, $body);
        $body = $this->prepareBody($method, $headers, $body);

        $rawCookies = $headers['cookie'] ?? '';
        $rawCookies = is_string($rawCookies) ? $rawCookies : '';

        $this->setCookies($rawCookies);

        $this->kernelBrowser->request($method, $uri, $parameters, [], $headers, $body);

        $symfonyResponse = $this->kernelBrowser->getResponse();

        $response = $this->httpMessageFactory->createResponse($symfonyResponse);
        $response->getBody()->rewind();

        return $response;
    }

    private function setCookies(string $rawCookies): void
    {
        $rawCookies = trim($rawCookies);
        if ('' === $rawCookies) {
            return;
        }

        $cookieKeyValuePairs = explode('; ', $rawCookies);

        foreach ($cookieKeyValuePairs as $cookieKeyValuePair) {
            $this->kernelBrowser->getCookieJar()->set(Cookie::fromString($cookieKeyValuePair));
        }
    }

    /**
     * @param array<mixed> $headers
     *
     * @return array<mixed>
     */
    private function prepareHeaders(array $headers): array
    {
        $mutatedHeaders = [];
        foreach ($headers as $key => $value) {
            $mutatedHeaders[strtolower($key)] = $value;
        }

        foreach ($mutatedHeaders as $key => $value) {
            if ('content-type' === $key || 'content_type' === $key) {
                $mutatedHeaders['CONTENT_TYPE'] = $value;
            } else {
                $mutatedHeaders['HTTP_' . strtoupper($key)] = $value;
                unset($mutatedHeaders[$key]);
            }
        }

        unset($mutatedHeaders['content_type'], $mutatedHeaders['HTTP_COOKIE']);

        return $mutatedHeaders;
    }

    /**
     * @param array<mixed> $headers
     *
     * @return array<mixed>
     */
    private function prepareParameters(string $method, array $headers, ?string $body): array
    {
        return $this->isMutationRequestWithFormPayload($method, $headers)
            ? (function () use ($body) {
                parse_str((string) $body, $parameters);

                return $parameters;
            })()
            : [];
    }

    /**
     * @param array<mixed> $headers
     */
    private function prepareBody(string $method, array $headers, ?string $body): ?string
    {
        return $this->isMutationRequestWithFormPayload($method, $headers) ? null : $body;
    }

    /**
     * @param array<mixed> $headers
     */
    private function isMutationRequestWithFormPayload(string $method, array $headers): bool
    {
        return ('POST' === $method || 'PUT' === $method)
            && 'application/x-www-form-urlencoded' === ($headers['content-type'] ?? null);
    }
}
