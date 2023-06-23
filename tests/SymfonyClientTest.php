<?php

declare(strict_types=1);

namespace SmartAssert\Tests\SymfonyTestClient;

use GuzzleHttp\Psr7\HttpFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SmartAssert\SymfonyTestClient\SymfonyClient;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class SymfonyClientTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider makeRequestDataProvider
     *
     * @param array<string, string> $headers
     * @param array<string, string> $expectedRequestParameters
     * @param string[][]            $expectedRequestHeaders
     */
    public function testMakeRequest(
        string $method,
        string $uri,
        array $headers,
        ?string $body,
        array $expectedRequestParameters,
        array $expectedRequestHeaders,
        ?string $expectedBody,
    ): void {
        $fooFactory = new HttpFactory();

        $factory = new PsrHttpFactory($fooFactory, $fooFactory, $fooFactory, $fooFactory);

        $client = new SymfonyClient($factory);

        $symfonyResponse = $this->createSymfonyResponse();
        $kernelBrowser = \Mockery::mock(KernelBrowser::class);

        $kernelBrowser
            ->shouldReceive('request')
            ->withArgs(
                function (
                    $passedMethod,
                    $passedUri,
                    $passedParameters,
                    $passedServer,
                    $passedHeaders,
                    $passedBody
                ) use (
                    $method,
                    $uri,
                    $expectedRequestParameters,
                    $expectedRequestHeaders,
                    $expectedBody
                ) {
                    self::assertSame($method, $passedMethod);
                    self::assertSame($uri, $passedUri);
                    self::assertSame($expectedRequestParameters, $passedParameters);
                    self::assertSame([], $passedServer);
                    self::assertSame($expectedRequestHeaders, $passedHeaders);
                    self::assertSame($expectedBody, $passedBody);

                    return true;
                }
            )
        ;
        $kernelBrowser
            ->shouldReceive('getResponse')
            ->andReturn($symfonyResponse)
        ;

        $client->setKernelBrowser($kernelBrowser);

        $receivedResponse = $client->makeRequest($method, $uri, $headers, $body);

        self::assertSame($symfonyResponse->getStatusCode(), $receivedResponse->getStatusCode());
        self::assertSame($symfonyResponse->headers->all(), $receivedResponse->getHeaders());
        self::assertSame($symfonyResponse->getContent(), $receivedResponse->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function makeRequestDataProvider(): array
    {
        $arbitraryBody = md5((string) rand());
        $formPayload = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $formContentBody = http_build_query($formPayload);

        return [
            'GET request without headers, without body' => [
                'method' => 'GET',
                'uri' => 'https://example.com/get-without-headers-without-body',
                'headers' => [],
                'body' => null,
                'expectedRequestParameters' => [],
                'expectedRequestHeaders' => [],
                'expectedBody' => null,
            ],
            'GET request with transformable headers, without body' => [
                'method' => 'GET',
                'uri' => 'https://example.com/get-with-headers-without-body',
                'headers' => [
                    'header1' => 'value1',
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer: token',
                    'HEADER2' => 'VALUE2',
                ],
                'body' => null,
                'expectedRequestParameters' => [],
                'expectedRequestHeaders' => [
                    'header1' => 'value1',
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer: token',
                    'header2' => 'VALUE2',
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_AUTHORIZATION' => 'Bearer: token',
                ],
                'expectedBody' => null,
            ],
            'POST request with without headers, with arbitrary body' => [
                'method' => 'POST',
                'uri' => 'https://example.com/post-without-headers-with-arbitrary-body',
                'headers' => [],
                'body' => $arbitraryBody,
                'expectedRequestParameters' => [],
                'expectedRequestHeaders' => [],
                'expectedBody' => $arbitraryBody,
            ],
            'POST request with with application/x-www-form-urlencoded content' => [
                'method' => 'POST',
                'uri' => 'https://example.com/post-with-encoded-form-content',
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $formContentBody,
                'expectedRequestParameters' => $formPayload,
                'expectedRequestHeaders' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                ],
                'expectedBody' => null,
            ],
            'PUT request with with application/x-www-form-urlencoded content' => [
                'method' => 'PUT',
                'uri' => 'https://example.com/put-with-encoded-form-content',
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $formContentBody,
                'expectedRequestParameters' => $formPayload,
                'expectedRequestHeaders' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                ],
                'expectedBody' => null,
            ],
        ];
    }

    private function createSymfonyResponse(): Response
    {
        $statusCode = rand(100, 599);
        $content = md5((string) rand());

        $headers = [];
        for ($i = 0; $i < 10; ++$i) {
            $headers[md5((string) rand())] = [
                md5((string) rand()),
            ];
        }

        return new Response($content, $statusCode, $headers);
    }
}
