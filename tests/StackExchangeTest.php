<?php

namespace AlexMasterov\OAuth2\Client\Tests\Provider;

use AlexMasterov\OAuth2\Client\Provider\Exception\StackExchangeException;
use AlexMasterov\OAuth2\Client\Provider\StackExchange;
use Eloquent\Phony\Phpunit\Phony;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class StackExchangeTest extends TestCase
{
    /**
     * @var StackExchange
     */
    private $provider;

    protected function setUp()
    {
        $this->provider = new StackExchange([
            'clientId'     => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri'  => 'mock_redirect_uri',
        ]);
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    protected function mockResponse($body)
    {
        $response = Phony::mock(ResponseInterface::class);
        $response->getHeader->with('content-type')->returns('application/json');
        $response->getBody->returns(json_encode($body));

        return $response;
    }

    protected function mockClient(ResponseInterface $response)
    {
        $client = Phony::mock(ClientInterface::class);
        $client->send->returns($response);

        return $client;
    }

    protected function getMethod($class, $name)
    {
        $class = new \ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testAuthorizationUrl()
    {
        // Run
        $url = $this->provider->getAuthorizationUrl();
        $path = \parse_url($url, PHP_URL_PATH);

        // Verify
        $this->assertSame('/oauth', $path);
    }

    public function testBaseAccessTokenUrl()
    {
        $params = [];

        // Run
        $url = $this->provider->getBaseAccessTokenUrl($params);
        $path = \parse_url($url, PHP_URL_PATH);

        // Verify
        $this->assertSame('/oauth/access_token', $path);
    }

    public function testDefaultScopes()
    {
        // Run
        $method = $this->getMethod(get_class($this->provider), 'getDefaultScopes');
        $result = $method->invoke($this->provider);

        // Verify
        $this->assertEquals([], $result);
    }

    public function testGetAccessToken()
    {
        // https://api.stackexchange.com/docs/authentication
        $body = [
            'access_token'  => 'mock_access_token',
            'token_type'    => 'bearer',
            'expires_in'    => \time() * 3600,
            'refresh_token' => 'mock_refresh_token',
        ];

        $response = $this->mockResponse($body);
        $client = $this->mockClient($response->get());

        // Run
        $this->provider->setHttpClient($client->get());
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        // Verify
        $this->assertNull($token->getResourceOwnerId());
        $this->assertEquals($body['access_token'], $token->getToken());
        $this->assertEquals($body['refresh_token'], $token->getRefreshToken());
        $this->assertGreaterThanOrEqual($body['expires_in'], $token->getExpires());
    }

    public function testUserProperty()
    {
        $body = [
            'items' => [
                0 => [
                    'user_id' => 12345678,
                ],
            ],
        ];

        $tokenOptions = [
            'access_token' => 'mock_access_token',
            'expires_in'   => 3600,
        ];

        $token = new AccessToken($tokenOptions);
        $response = $this->mockResponse($body);
        $client = $this->mockClient($response->get());

        // Run
        $this->provider->setHttpClient($client->get());
        $user = $this->provider->getResourceOwner($token);

        // Verify
        $this->assertSame([$body['items'][0]['user_id']], $user->getId());

        foreach ($user->toArray() as $user) {
            $this->assertArrayHasKey('user_id', $user);
        }
    }

    public function testParseResponse()
    {
        $body = 'access_token=mock_access_token&expires=3600';
        parse_str($body, $parsed);

        $response = Phony::mock(ResponseInterface::class);
        $response->getHeader->with('content-type')->returns('text/plain');
        $response->getBody->returns($body);
        $client = $this->mockClient($response->get());

        // Run
        $method = $this->getMethod(get_class($this->provider), 'parseResponse');
        $result = $method->invoke($this->provider, $response->get());

        // Verify
        $this->assertEquals($parsed, $result);
    }

    public function testErrorResponses()
    {
        $code = 400;
        $body = [
            'error' => [
                'type'    => 'Foo error',
                'message' => 'Error message',
            ],
        ];

        $response = $this->mockResponse($body);
        $response->getStatusCode->returns($code);
        $client = $this->mockClient($response->get());

        $this->expectException(StackExchangeException::class);
        $this->expectExceptionCode($code);
        $this->expectExceptionMessage(implode(': ', $body['error']));

        // Run
        $this->provider->setHttpClient($client->get());
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
