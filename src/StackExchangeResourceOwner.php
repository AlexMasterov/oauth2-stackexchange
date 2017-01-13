<?php

namespace AlexMasterov\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class StackExchangeResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * @var array
     */
    protected $response = [];

    /**
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $this->getValueByKey($response, 'items', []);
    }

    /**
     * @return array
     */
    public function getId()
    {
        $ids = [];
        foreach ($this->response as $item) {
            $ids[] = $this->getValueByKey($item, 'user_id');
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
