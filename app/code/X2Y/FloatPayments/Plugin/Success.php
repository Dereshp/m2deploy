<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright 2022 (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Plugin;

use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use X2Y\FloatPayments\Helper\Data as Helper;

/**
 * Order success page interceptor
 */
class Success
{
    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CartRepositoryInterface
     */
    protected $_cartRepository;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var JsonSerializer
     */
    protected $_jsonSerializer;

    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonSerializer $jsonSerializer
     * @param Helper $helper
     */
    public function __construct(
        CheckoutSession          $checkoutSession,
        CartRepositoryInterface  $cartRepository,
        OrderRepositoryInterface $orderRepository,
        JsonSerializer           $jsonSerializer,
        Helper                   $helper
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_cartRepository = $cartRepository;
        $this->_orderRepository = $orderRepository;
        $this->_jsonSerializer = $jsonSerializer;
        $this->_helper = $helper;
    }

    /**
     * @param \Magento\Checkout\Controller\Onepage\Success $subject
     * @return void
     */
    public function beforeExecute(\Magento\Checkout\Controller\Onepage\Success $subject)
    {
        $this->_request = $subject->getRequest();
        $orderId = $this->_request->getParam('order_id');
        $transactionVerify = $this->_request->getParam('transaction_verify');

        // Make sure request is coming from Float system
        if (!$orderId || !$transactionVerify) return;

        try {
            $order = $this->_orderRepository->get($orderId);
            $quote = $this->_cartRepository->get($order->getQuoteId());

            if (!$this->isValid($quote)) return;

            // Restoring customers checkout session
            // @see \Magento\Checkout\Model\Session\SuccessValidator::isValid()
            // @see \Magento\Checkout\Controller\Onepage\Success::execute()
            $this->_checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->_checkoutSession->setLastQuoteId($quote->getId());
            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrder($order);
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastOrderStatus($order->getStatus());
        } catch (\Exception $e) {
            $this->_helper->logError('Order Success Page');
            $this->_helper->logError($e->getMessage());
        }
    }

    /**
     * Validate Float redirect request
     *
     * @param Quote $quote
     * @return bool
     */
    private function isValid(Quote $quote): bool
    {
        $transactionMatch = false;
        $amountMatch = false;

        $paymentInformation = $this->getPaymentInformation($quote);
        $transactionVerify = $this->_request->getParam('transaction_verify');

        if (rtrim($transactionVerify, '/') == $paymentInformation['transaction_verify']) {
            $transactionMatch = true;
        }

        $amount = $paymentInformation['payment_request']['purchaseAmount'];
        if ($quote->getGrandTotal() * 100 == $amount) {
            $amountMatch = true;
        }

        $this->_helper->logDebug(
            sprintf(
                'Transaction validation result: amount: [saved - %s, in quote - %s]' .
                ' transaction_verify code [saved - %s, in requests - %s]',
                $amount,
                $quote->getGrandTotal(),
                $paymentInformation['transaction_verify'],
                rtrim($transactionVerify, '/')
            )
        );

        return $transactionMatch && $amountMatch;
    }

    /**
     * Get Float payment information from quote
     *
     * @param Quote $quote
     * @return string[]
     */
    private function getPaymentInformation(Quote $quote): array
    {
        $paymentInformation = $quote->getPayment()->getAdditionalInformation(Helper::PAYMENT_ADD_INFO_KEY);

        $this->_helper->logDebug(
            sprintf(
                'Validating success request, quote id: %s, payment id %s',
                (string)$quote->getId(),
                (string)$quote->getPayment()->getId()
            )
        );

        $this->_helper->logDebug($this->_jsonSerializer->serialize($paymentInformation));

        if (is_string($paymentInformation)) {
            return $this->_jsonSerializer->unserialize($paymentInformation);
        }

        if (is_null($paymentInformation)) {
            return ['transaction_verify' => ''];
        }

        return $paymentInformation;
    }
}
