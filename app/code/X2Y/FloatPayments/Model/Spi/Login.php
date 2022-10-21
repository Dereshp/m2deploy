<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Model\Spi;

use Magento\Framework\HTTP\Client\Curl;
use X2Y\FloatPayments\Api\Data\LoginResultInterface;
use X2Y\FloatPayments\Api\Data\LoginResultInterfaceFactory;
use X2Y\FloatPayments\Helper\Data as FloatHelper;
use Magento\Framework\HTTP\Client\CurlFactory as ClientCurlFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

/**
 * Get float login data
 */
class Login implements LoginInterface
{
    /**
     * @var FloatHelper
     */
    private $floatHelper;
    /**
     * @var ClientCurlFactory
     */
    private $curl;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var LoginResultInterfaceFactory
     */
    private $loginResultInterfaceFactory;
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @param FloatHelper $floatHelper
     * @param ClientCurlFactory $curl
     * @param JsonSerializer $jsonSerializer
     * @param LoginResultInterfaceFactory $loginResultInterfaceFactory
     * @param ConfigWriter $configWriter
     */
    public function __construct(
        FloatHelper $floatHelper,
        ClientCurlFactory $curl,
        JsonSerializer $jsonSerializer,
        LoginResultInterfaceFactory $loginResultInterfaceFactory,
        ConfigWriter $configWriter
    ) {
        $this->floatHelper = $floatHelper;
        $this->curl = $curl;
        $this->jsonSerializer = $jsonSerializer;
        $this->loginResultInterfaceFactory = $loginResultInterfaceFactory;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritDoc
     */
    public function execute(): LoginResultInterface
    {

        $body = $this->getFromConfig();
        if (!$body) {
            $body = $this->getFromFloatApi();
        }

        if (isset($body['status']) && $body['status'] == 404) {
            throw new \Exception($body['error']);
        }

        /** @var LoginResultInterface $result */
        $result = $this->loginResultInterfaceFactory->create();
        $result->setId($body['merchant']['id'] ?? '');
        $result->setName($body['merchant']['name'] ?? '');
        $result->setTermRules($body['merchant']['rules'] ?? []);
        $result->setToken($body['token']);

        return $result;
    }

    /**
     * Get saved login data
     *
     * @return array
     */
    private function getFromConfig(): array
    {
        $token = $this->floatHelper->getApiGroupInfo('token');
        $loginData = $this->floatHelper->getApiGroupInfo('login_data');

        if (!$token || !$loginData) {
            return [];
        }

        return $this->jsonSerializer->unserialize($loginData);
    }

    /**
     * Get login data from Float api
     *
     * @return array
     */
    private function getFromFloatApi(): array
    {
        /** @var Curl $curl */
        $curl = $this->curl->create();
        $postData = [
            'merchant_id' => $this->floatHelper->getApiGroupInfo('merchant_id'),
            'client_id' => $this->floatHelper->getApiGroupInfo('client_id'),
            'client_secret' => $this->floatHelper->getApiGroupInfo('client_secret')
        ];

        $curl->post(
            $this->floatHelper->getApiUrl() . '/login',
            $postData
        );

        // Debug responses
        $this->floatHelper->logDebug('Login request:');
        $this->floatHelper->logDebug($curl->getBody());

        $body = $this->jsonSerializer->unserialize($curl->getBody());
        // Save login data into db
        $this->configWriter->save(FloatHelper::XPATH_API . 'token', $body['token']);
        $this->configWriter->save(FloatHelper::XPATH_API . 'login_data', $curl->getBody());

        return $body;
    }
}
