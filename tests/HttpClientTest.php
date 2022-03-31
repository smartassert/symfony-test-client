<?php

declare(strict_types=1);

namespace SmartAssert\Tests\SymfonyTestClient;

use GuzzleHttp\Psr7\HttpFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SmartAssert\SymfonyTestClient\HttpClient;

/**
 * @internal
 * @coversNothing
 */
class HttpClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider makeRequestDataProvider
     *
     * @param array<string, string> $headers
     * @param string[][]            $expectedRequestHeaders
     */
    public function testMakeRequest(
        string $method,
        string $uri,
        array $headers,
        ?string $body,
        array $expectedRequestHeaders,
    ): void {
        $response = \Mockery::mock(ResponseInterface::class);

        $httpClient = \Mockery::mock(HttpClientInterface::class);
        $httpClient
            ->shouldReceive('sendRequest')
            ->withArgs(function (RequestInterface $request) use ($method, $uri, $expectedRequestHeaders, $body) {
                self::assertSame($method, $request->getMethod());
                self::assertSame($uri, (string) $request->getUri());
                self::assertSame($expectedRequestHeaders, $request->getHeaders());
                self::assertSame((string) $body, $request->getBody()->getContents());

                return true;
            })
            ->andReturn($response)
        ;

        $httpFactory = new HttpFactory();

        $client = new HttpClient($httpFactory, $httpFactory, $httpClient);
        $receivedResponse = $client->makeRequest($method, $uri, $headers, $body);

        self::assertSame($response, $receivedResponse);
    }

    /**
     * @return array<mixed>
     */
    public function makeRequestDataProvider(): array
    {
        return [
            'GET request without headers, without body' => [
                'method' => 'GET',
                'uri' => 'https://example.com/get-without-headers-without-body',
                'headers' => [],
                'body' => null,
                'expectedRequestHeaders' => [
                    'Host' => [
                        'example.com',
                    ],
                ],
            ],
            'GET request with headers, with body' => [
                'method' => 'GET',
                'uri' => 'https://example.com/get-with-headers-with-body',
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => (string) json_encode([
                    'foo1' => 'bar1',
                    'foo2' => 'bar2',
                    'foo3' => 'bar3',
                ]),
                'expectedRequestHeaders' => [
                    'Host' => [
                        'example.com',
                    ],
                    'content-type' => [
                        'application/json',
                    ],
                ],
            ],
            'POST request with headers, with body' => [
                'method' => 'POST',
                'uri' => 'https://example.com/post-with-headers-with-body',
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => (string) json_encode([
                    'foo1' => 'bar1',
                    'foo2' => 'bar2',
                    'foo3' => 'bar3',
                ]),
                'expectedRequestHeaders' => [
                    'Host' => [
                        'example.com',
                    ],
                    'content-type' => [
                        'application/json',
                    ],
                ],
            ],
        ];
    }
}
