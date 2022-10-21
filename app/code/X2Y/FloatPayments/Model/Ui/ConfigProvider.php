<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use X2Y\FloatPayments\Helper\Data;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Float payment config provider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var Data
     */
    private $data;

    /**
     * @param ConfigInterface $config
     * @param Data $data
     */
    public function __construct(ConfigInterface $config, Data $data)
    {
        $this->config = $config;
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                Data::METHOD_CODE => [
                    'isActive' => $this->isActive()
                ]
            ]
        ];
    }

    /**
     * Check is active
     *
     * @return bool
     */
    private function isActive(): bool
    {
        // Check if credentials set, api url exist and payment method enabled
        return $this->data->getApiGroupInfo('merchant_id') &&
            $this->data->getApiGroupInfo('client_id') &&
            $this->data->getApiGroupInfo('client_secret') &&
            $this->data->getApiUrl() &&
            $this->config->getValue('active');
    }
}
