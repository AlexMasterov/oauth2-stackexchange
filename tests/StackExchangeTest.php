<?php
declare(strict_types=1);

namespace AlexMasterov\OAuth2\Client\Provider\Tests;

use AlexMasterov\OAuth2\Client\Provider\{
    StackExchange,
    StackExchangeException,
    StackExchangeResourceOwner,
    Tests\CanAccessTokenStub,
    Tests\CanMockHttp
};
use PHPUnit\Framework\TestCase;

class StackExchangeTest extends TestCase
{
    use CanAccessTokenStub;
    use CanMockHttp;

    public function testAuthorizationUrl()
    {
        // Execute
        $url = $this->provider()
            ->getAuthorizationUrl();

        // Verify
        self::assertSame('/oauth', path($url));
    }

    public function testBaseAccessTokenUrl()
    {
        static $params = [];

        // Execute
        $url = $this->provider()
            ->getBaseAccessTokenUrl($params);

        // Verify
        self::assertSame('/oauth/access_token', path($url));
    }

    public function testResourceOwnerDetailsUrl()
    {
        // Stub
        $apiUrl = $this->apiUrl();
        $tokenParams = [
            'access_token' => 'mock_access_token',
            'site' => 'stackoverflow',
        ];

        list($accessToken, $site) = array_values($tokenParams);

        // Execute
        $detailUrl = $this->provider()
            ->getResourceOwnerDetailsUrl($this->accessToken($tokenParams));

        // Verify
        self::assertSame(
            "{$apiUrl}me?access_token={$accessToken}&site={$site}",
            $detailUrl
        );
    }

    public function testDefaultScopes()
    {
        $getDefaultScopes = function () {
            return $this->getDefaultScopes();
        };

        // Execute
        $defaultScopes = $getDefaultScopes->call($this->provider());

        // Verify
        self::assertSame([], $defaultScopes);
    }

    public function testParseResponse()
    {
        $getParseResponse = function ($response) {
            return $this->parseResponse($response);
        };

        // Mock
        $plain = $this->mockResponse('mock_body=test', 'text/plain');
        $default = $this->mockResponse(json_encode(['mock_body' => 'test']));

        // Execute
        $parsedPlain = $getParseResponse->call($this->provider(), $plain);
        $parsedDefault = $getParseResponse->call($this->provider(), $default);

        // Verify
        self::assertSame(['mock_body' => 'test'], $parsedPlain);
        self::assertSame(['mock_body' => 'test'], $parsedDefault);
    }

    public function testCheckResponse()
    {
        $getParseResponse = function () use (&$response, &$data) {
            return $this->checkResponse($response, $data);
        };

        // Stub
        $code = 400;
        $data = ['error' => [
            'type'    => 'Foo error',
            'message' => 'Error message',
        ]];

        // Mock
        $response = $this->mockResponse('', '', $code);

        // Verify
        self::expectException(StackExchangeException::class);
        self::expectExceptionCode($code);
        self::expectExceptionMessage(implode(': ', $data['error']));

        // Execute
        $getParseResponse->call($this->provider());
    }

    public function testCreateResourceOwner()
    {
        $getCreateResourceOwner = function () use (&$response, &$token) {
            return $this->createResourceOwner($response, $token);
        };

        // Stub
        $token = $this->accessToken();
        $response = ['items' => [
            0 => ['user_id' => random_int(1, 1000)],
        ]];

        // Execute
        $resourceOwner = $getCreateResourceOwner->call($this->provider());

        // Verify
        self::assertInstanceOf(StackExchangeResourceOwner::class, $resourceOwner);

        $items = $response['items'];
        $ids = array_values($items[0]);

        self::assertEquals($ids, $resourceOwner->getId());
        self::assertSame($items, $resourceOwner->toArray());
    }

    private function provider(...$args): StackExchange
    {
        static $default = [
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'mock_redirect_uri',
        ];

        $values = array_replace($default, ...$args);

        return new StackExchange($values);
    }

    private function apiUrl(): string
    {
        $getApiUrl = function () {
            return $this->urlApi;
        };

        return $getApiUrl->call($this->provider());
    }
}

function path(string $url): string
{
    return parse_url($url, PHP_URL_PATH);
}
