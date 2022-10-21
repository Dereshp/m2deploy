<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

declare(strict_types=1);

namespace X2Y\FloatPayments\Block;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use X2Y\FloatPayments\Helper\Data as FloatHelper;
use X2Y\FloatPayments\Model\Config\Source\Mode;

/**
 * Class PreviewPayment.
 *
 * @package X2Y\FloatPayments\Block
 */
class PaymentPlanPreview extends Template
{
    /**
     * @var HttpRequest
     */
    private $httpRequest;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var FloatHelper
     */
    private $floatHelper;
    /**
     * @var FormatInterface
     */
    private $localeFormat;

    /**
     * PreviewPayment constructor.
     * @param Template\Context $context
     * @param HttpRequest $httpRequest
     * @param ProductRepositoryInterface $productRepository
     * @param Json $json
     * @param FloatHelper $floatHelper
     * @param FormatInterface $localeFormat
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HttpRequest $httpRequest,
        ProductRepositoryInterface $productRepository,
        Json $json,
        FloatHelper $floatHelper,
        FormatInterface $localeFormat,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->httpRequest = $httpRequest;
        $this->productRepository = $productRepository;
        $this->json = $json;
        $this->floatHelper = $floatHelper;
        $this->localeFormat = $localeFormat;
    }

    /**
     * Get max payment plan months
     *
     * @return int
     */
    public function getMaxMonths(): int
    {
        $data = $this->json->unserialize($this->floatHelper->getApiGroupInfo('login_data') ?? '{}');
        $terms = $data['merchant']['rules']['terms'] ?? [];
        return (int) end($terms);
    }

    /**
     * @return float
     */
    private function _getProductPrice(): float
    {
        $productPrice = 0;
        $productId = $this->httpRequest->getParam('id', null);
        if ($productId) {
            try {
                $product = $this->productRepository->getById($productId);
                $productPrice = ($product->getTypeId() == Type::TYPE_CODE || $product->getTypeId() == Configurable::TYPE_CODE)
                    ? $product->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue()
                    : $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            } catch (NoSuchEntityException $entityException) {
                $productPrice = 0;
            }
        }

        return $productPrice;
    }


    /**
     * @return string
     */
    public function getHowItWorksUrl(): string
    {
        return ($this->floatHelper->getApiGroupInfo('mode') == Mode::PRODUCTION)
            ? '//secure.float.co.za/home/how-to-float#no-scroll'
            : '//uat-secure.float.co.za/home/how-to-float#no-scroll';
    }

    /**
     * @return string
     */
    public function getJsonConfig(): string
    {
        $config = [
            'productPrice' => $this->_getProductPrice(),
            'maxMonths' => $this->getMaxMonths(),
            'priceFormat' => $this->localeFormat->getPriceFormat()
        ];

        return $this->json->serialize($config);
    }
}
