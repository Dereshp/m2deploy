<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use X2Y\FloatPayments\Helper\Data;

/**
 * Multishipping checkout component view model
 */
class MultishippingComponent implements ArgumentInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return int
     */
    public function getCartId(): int
    {
        return $this->checkoutSession->getQuoteId();
    }

    /**
     * Get payment method
     *
     * @return bool
     */
    public function isPaymentFloat(): bool
    {
        try {
            return $this->checkoutSession->getQuote()->getPayment()->getMethod() == Data::METHOD_CODE;
        } catch (\Exception $e) {
            return false;
        }
    }
}
