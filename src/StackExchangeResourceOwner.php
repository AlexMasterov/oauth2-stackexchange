<?php
declare(strict_types=1);

namespace AlexMasterov\OAuth2\Client\Provider;

use League\OAuth2\Client\{
    Provider\ResourceOwnerInterface,
    Tool\ArrayAccessorTrait
};

class StackExchangeResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    public function __construct(array $response = [])
    {
        $this->items = $this->getValueByKey($response, 'items', []);
    }

    /**
     * @return array
     */
    public function getId()
    {
        $items = $this->items;

        $ids = [];
        foreach ($items as $item) {
            $ids[] = $this->getValueByKey($item, 'user_id');
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * @var array
     */
    protected $items = [];
}
