<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Gateway;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use X2Y\FloatPayments\Helper\Data as FloatHelper;

/**
 * Sale payment command
 */
class SaleCommand implements CommandInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $payment = $commandSubject["payment"]->getPayment();
        $floatInfo = $payment->getAdditionalInformation(FloatHelper::PAYMENT_ADD_INFO_KEY);
        $payment->setTransactionId($floatInfo['payment_response']['transaction_id'] ?? '');
    }
}
