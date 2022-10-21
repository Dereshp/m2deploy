<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use X2Y\FloatPayments\Model\Config\Source\Mode;
use Magento\Framework\Encryption\EncryptorInterface;
use Monolog\Logger as MonologLogger;

/**
 * Payment method helper
 */
class Data extends AbstractHelper
{
    const METHOD_CODE   = 'float';
    const XPATH_PATTERN = 'payment/%s/general/%s';
    const XPATH_GENERAL = 'payment/float/general/';
    const XPATH_API     = 'payment/float/api/';
    const PAYMENT_ADD_INFO_KEY = 'float_info';
    const CREDENTIALS_KEYS = [
        'merchant_id',
        'client_id',
        'client_secret'
    ];

    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var MonologLogger
     */
    private $logger;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param MonologLogger $logger
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        MonologLogger $logger
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->logger = $logger;
    }

    /**
     * Get general information
     *
     * @param string $path
     * @param string $scope
     * @return mixed
     */
    public function getGeneralGroupInfo(string $path, string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(self::XPATH_GENERAL . $path, $scope);
    }

    /**
     * Get api data
     *
     * @param string $path
     * @param string $scope
     * @return mixed
     */
    public function getApiGroupInfo(string $path, string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        $value = $this->scopeConfig->getValue(self::XPATH_API . $path, $scope);
        if (in_array($path, self::CREDENTIALS_KEYS)) {
            return $this->encryptor->decrypt($value);
        }

        return $value;
    }

    /**
     * @return mixed
     */
    public function getApiUrl()
    {
        $mode = $this->getApiGroupInfo('mode');
        if ($mode == Mode::SANDBOX) {
            return $this->getApiGroupInfo('url_sandbox');
        }

        return $this->getApiGroupInfo('url_production');
    }

    /**
     * Notify url
     *
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->_getUrl('float/notify');
    }

    /**
     * Get Successful Order Status
     *
     * @return int
     */
    public function getSuccessfulOrderStatus() {
        return $this->getGeneralGroupInfo('successful_order_status');
    }

    /**
     * Write debug message into custom float.log file
     *
     * @param string $message
     */
    public function logDebug(string $message)
    {
        if ($this->getGeneralGroupInfo('debug')) {
            $this->logger->debug($message);
        }
    }

    /**
     * Write error message into custom float.log file
     *
     * @param string $message
     */
    public function logError(string $message)
    {
        if ($this->getGeneralGroupInfo('debug')) {
            $this->logger->error($message);
        }
    }
}
