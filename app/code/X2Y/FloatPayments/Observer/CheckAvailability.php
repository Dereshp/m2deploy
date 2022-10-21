<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Model\MethodInterface;
use X2Y\FloatPayments\Helper\Data as FloatHelper;
use X2Y\FloatPayments\Model\Spi\LoginInterface;

/**
 * Enable/disable Float payment if minAmount > quote total OR quote total > maxAmount
 */
class CheckAvailability implements ObserverInterface
{
    /**
     * @var LoginInterface
     */
    private $login;
    /**
     * @var FloatHelper
     */
    private $floatHelper;

    /**
     * @param LoginInterface $login
     * @param FloatHelper $floatHelper
     */
    public function __construct(
        LoginInterface $login,
        FloatHelper $floatHelper
    ) {
        $this->login = $login;
        $this->floatHelper = $floatHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $checkResult = $observer->getEvent()->getData('result');
        $quote = $observer->getEvent()->getData('quote');
        /** @var MethodInterface $method */
        $method = $observer->getEvent()->getData('method_instance');

        if (!$quote || $method->getCode() !== FloatHelper::METHOD_CODE) {
            return;
        }

        try {
            $loginData = $this->login->execute();
            $terms = $loginData->getTermRules();
            if (!$terms) {
                $checkResult->setData('is_available', false);
                return;
            }

            $maxAmount = $terms['toAmount'];
            $minAmount = $terms['fromAmount'];

            $totalInCents = $quote->getGrandTotal() * 100;
            if ($totalInCents > $maxAmount || $totalInCents < $minAmount) {
                $checkResult->setData('is_available', false);
            }
        } catch (\Exception $e) {
            $checkResult->setData('is_available', false);
            $this->floatHelper->logError('Error on check payment method availability:');
            $this->floatHelper->logError($e->getMessage());
        }
    }
}
