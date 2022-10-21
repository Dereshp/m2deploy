<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright 2022 (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Controller\Notify;

use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use X2Y\FloatPayments\Helper\Data as FloatHelper;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Process customer payment data
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var FloatHelper
     */
    private $floatHelper;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param UrlInterface $url
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param JsonSerializer $jsonSerializer
     * @param FloatHelper $floatHelper
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        UrlInterface $url,
        QuoteCollectionFactory $quoteCollectionFactory,
        JsonSerializer $jsonSerializer,
        FloatHelper $floatHelper,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        CustomerSession $customerSession
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->url = $url;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->floatHelper = $floatHelper;
        $this->cartManagement = $cartManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $orderId = $this->request->getParam('order_id');

        // TODO: refactor through a search criteria instead of collection
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->addFieldToFilter('reserved_order_id', $orderId);
        $quote = $quoteCollection->getFirstItem();

        try {
            // We need to load quote via cart repository to make sure all data is loaded
            $quote = $this->cartRepository->get($quote->getId());

            if (!$this->isResponseValid($quote)) {
                $result->setData(['redirect' => $this->url->getUrl('checkout/cart')]);

                $this->floatHelper->logError('Response is not valid, redirecting to cart.');
                return $result;
            }

            if ((bool)$quote->getCustomerIsGuest()) {
                $quote->setCustomerId(null)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(
                        Group::NOT_LOGGED_IN_ID
                    );
                $this->cartRepository->save($quote);
            }

            $quote->setTotalsCollectedFlag(true);
            $orderId = $this->cartManagement->placeOrder($quote->getId());

            $order = $this->orderRepository->get($orderId);
            $order->setStatus($this->floatHelper->getSuccessfulOrderStatus());
            $this->orderRepository->save($order);

            $result->setData([
                'redirect' => $this->url->getUrl(
                    'checkout/onepage/success',
                    [
                        'transaction_verify' => $this->request->getParam('transaction_verify'),
                        'order_id' => $order->getId()
                    ]
                )
            ]);

            $this->floatHelper->logDebug('Place order success. ORDER ID:' . $orderId);
        } catch (\Exception $e) {
            $this->floatHelper->logError('Place order failed.');
            $this->floatHelper->logError($e->getMessage());

            $result->setData(['redirect' => $this->url->getUrl('checkout/cart')]);
        }

        return $result;
    }

    /**
     * Validate response
     *
     * @param $quote
     * @return bool
     */
    private function isResponseValid($quote): bool
    {
        $status = $this->request->getParam('status');
        $orderId = $this->request->getParam('order_id');
        $transactionVerify = $this->request->getParam('transaction_verify');
        $floatInfo = $quote->getPayment()->getAdditionalInformation(FloatHelper::PAYMENT_ADD_INFO_KEY);

        $debugLog = array(
            'request' => $this->request->getParams(),
            'quote_id' => $quote->getId(),
            'info_in_payment' => $floatInfo
        );

        $this->floatHelper->logDebug($this->jsonSerializer->serialize($debugLog));

        // Check status
        if ($status != 'success') {
            return false;
        }
        // Check transaction id
        if ($floatInfo['transaction_verify'] != $transactionVerify) {
            return false;
        }
        // Check order id match
        if ($quote->getReservedOrderId() != $orderId) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        // TODO: check if origin is float
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        // TODO: check if origin is float
        return true;
    }
}
