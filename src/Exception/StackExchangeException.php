<?php

namespace AlexMasterov\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class StackExchangeException extends IdentityProviderException
{
    /**
     * @param ResponseInterface $response
     * @param string|array $data
     *
     * @return static
     */
    public static function errorResponse(ResponseInterface $response, $data)
    {
        $message = $data['error']['type'];

        if (!empty($data['error']['message'])) {
            $message .= ': '.$data['error']['message'];
        }

        $code = $response->getStatusCode();
        $body = (string) $response->getBody();

        return new static($message, $code, $body);
    }
}
