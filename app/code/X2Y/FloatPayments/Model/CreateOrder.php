<?php

/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Model;

use Magento\Quote\Api\Data\CartInterface;
use X2Y\FloatPayments\Api\CreateOrderInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface as QuoteMaskToQuoteId;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Api\CartRepositoryInterface;
use X2Y\FloatPayments\Api\Data\LoginResultInterface;
use X2Y\FloatPayments\Helper\Data as FloatHelper;
use X2Y\FloatPayments\Model\Config\Source\Mode;
use X2Y\FloatPayments\Model\Spi\LoginInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory as ClientCurlFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

/**
 * Post to float and get order details
 */
class CreateOrder implements CreateOrderInterface
{
    /**
     * @var QuoteMaskToQuoteId
     */
    private $maskToQuoteId;
    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var FloatHelper
     */
    private $floatHelper;
    /**
     * @var LoginInterface
     */
    private $login;
    /**
     * @var ClientCurlFactory
     */
    private $curlFactory;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var array
     */
    private $floatInfo = [];

    /**
     * @param QuoteMaskToQuoteId $maskToQuoteId
     * @param CustomerSession $customerSession
     * @param CartRepositoryInterface $cartRepository
     * @param FloatHelper $floatHelper
     * @param LoginInterface $login
     * @param ClientCurlFactory $curlFactory
     * @param JsonSerializer $jsonSerializer
     */
    public function __construct(
        QuoteMaskToQuoteId $maskToQuoteId,
        CustomerSession $customerSession,
        CartRepositoryInterface $cartRepository,
        FloatHelper $floatHelper,
        LoginInterface $login,
        ClientCurlFactory $curlFactory,
        JsonSerializer $jsonSerializer
    ) {
        $this->maskToQuoteId = $maskToQuoteId;
        $this->customerSession = $customerSession;
        $this->cartRepository = $cartRepository;
        $this->floatHelper = $floatHelper;
        $this->login = $login;
        $this->curlFactory = $curlFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $cartId): string
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                $cartId = $this->maskToQuoteId->execute($cartId);
            }
            $cart = $this->cartRepository->getActive($cartId);
            $cart->reserveOrderId();

            $result = $this->makePaymentCheckoutRequest($cart);
            $this->floatInfo['payment_response'] = $result;
            $this->floatInfo['transaction_verify'] = uniqid();

            // Save response information into quote payment
            $cart->getPayment()->setAdditionalInformation(
                FloatHelper::PAYMENT_ADD_INFO_KEY,
                $this->floatInfo
            );

            $this->cartRepository->save($cart);

            $orderUrl = $this->floatHelper->getApiUrl() . $result['payment_url'] .
                '?transaction_verify=' . $this->floatInfo['transaction_verify'];
        } catch (\Exception $e) {
            $this->floatHelper->logError('Float Payments is not available at the moment. Please try again later.');

            $orderUrl = '';
        }

        return $orderUrl;
    }

    /**
     * Make float api checkout/payment request
     *
     * @param $cart
     * @return array
     * @throws \Exception
     */
    private function makePaymentCheckoutRequest($cart): array
    {
        $loginData = $this->login->execute();
        $requestData = [
            'purchaseAmount' => $cart->getGrandTotal() * 100,
            'currency' => $cart->getQuoteCurrencyCode(),
            'customer' => $this->getCustomerArray($cart),
            'merchant' => $this->getMerchantArray($loginData),
            'purchase' => $this->getPurchaseArray($cart)
        ];

        // save request
        $this->floatInfo['payment_request'] = $requestData;
        $requestData = $this->jsonSerializer->serialize($requestData);

        /** @var Curl $curl */
        $curl = $this->curlFactory->create();
        $curl->addHeader('Authorization', 'Bearer ' . $loginData->getToken());
        $curl->addHeader(\Zend_Http_Client::CONTENT_TYPE, 'application/json');
        $curl->post(
            $this->floatHelper->getApiUrl() . '/payment/checkout',
            $requestData
        );
        $this->floatHelper->logDebug('Order create request:');
        $this->floatHelper->logDebug($requestData);

        $body = $curl->getBody();
        $this->floatHelper->logDebug('Order create response:');
        $this->floatHelper->logDebug($body);

        $body = $this->jsonSerializer->unserialize($body);

        if (isset($body['code']) && $body['code'] != 200) {
            throw new \Exception($curl->getBody());
        }

        return $body;
    }

    /**
     * Create purchase array
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getPurchaseArray(CartInterface $quote): array
    {
        $purchase = [
            'order_id' => $quote->getReservedOrderId(),
            'purchaseDate' => date('Y-m-d'),
            'items' => []
        ];
        $shippingAmount = $quote->getShippingAddress()->getShippingAmount() * 100;
        $taxAmount = 0;

        foreach ($quote->getItems() as $item) {
            $taxAmount += $item->getTaxAmount() * 100;
            $purchase['items'][] = [
                'description' => $item->getProduct()->getName(),
                'sku' => $item->getSku(),
                'price' => ($item->getRowTotal() - $item->getDiscountAmount()) * 100,
                'qty' => $item->getQty()
            ];
        }

        $purchase['items'][] = [
            'description' => 'Tax',
            'sku' => 'tax',
            'price' => $taxAmount,
            'qty' => 1
        ];
        $purchase['items'][] = [
            'description' => 'Shipping',
            'sku' => 'shipping',
            'price' => $shippingAmount,
            'qty' => 1
        ];

        $items = $purchase['items'];
        $purchase['items'] = json_encode($items);
        return $purchase;

        return $purchase;
    }

    /**
     * Get customer array
     *
     * @param CartInterface $quote
     * @return array
     */
    private function getCustomerArray(CartInterface $quote): array
    {
        $address = $quote->getBillingAddress();

        return [
            'name' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'telephoneNumber' => $address->getTelephone(),
            'emailAddress' => $address->getEmail(),
            'billingAddress' => $address->getStreetFull() . ', ' . $address->getCity()
        ];
    }

    /**
     * Get merchant array
     *
     * @param LoginResultInterface $loginData
     * @return array
     */
    private function getMerchantArray(LoginResultInterface $loginData): array
    {
        $mode = $this->floatHelper->getApiGroupInfo('mode') == Mode::PRODUCTION ?
            'live' : 'test';

        return [
            'merchantReference' => $this->floatHelper->getApiGroupInfo('merchant_id'),
            'name' => $loginData->getName(),
            'mode' => $mode,
            'return_url' => '',
            'notify_url' => $this->floatHelper->getNotifyUrl()
        ];
    }
}
