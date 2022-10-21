<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Model\Data;

use Magento\Framework\DataObject;
use X2Y\FloatPayments\Api\Data\LoginResultInterface;

/**
 * Implement login representation interface
 */
class LoginResult extends DataObject implements LoginResultInterface
{

    /**
     * @inheritDoc
     */
    public function setId(string $id): LoginResultInterface
    {
        return $this->setData(self::MERCHANT_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): LoginResultInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function setTermRules(array $termRules): LoginResultInterface
    {
        return $this->setData(self::TERM_RULES, $termRules);
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $token): LoginResultInterface
    {
        return $this->setData(self::ACCESS_TOKEN, $token);
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->getData(self::MERCHANT_ID);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function getTermRules(): array
    {
        return $this->getData(self::TERM_RULES);
    }

    /**
     * @inheritDoc
     */
    public function getToken(): string
    {
        return $this->getData(self::ACCESS_TOKEN);
    }
}
