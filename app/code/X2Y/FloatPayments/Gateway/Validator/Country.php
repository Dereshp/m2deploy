<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use X2Y\FloatPayments\Helper\Data as FloatHelper;

/**
 * Validate country
 */
class Country extends AbstractValidator
{
    /**
     * @var FloatHelper
     */
    private $floatHelper;

    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param FloatHelper $floatHelper
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        FloatHelper $floatHelper
    ) {
        parent::__construct($resultFactory);
        $this->floatHelper = $floatHelper;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $allowSpecific = $this->floatHelper->getGeneralGroupInfo('allowspecific') == 1;
        if ($allowSpecific) {
            $countries = $this->floatHelper->getGeneralGroupInfo('specificcountry');

            return $this->createResult(in_array($validationSubject['country'], explode(',', $countries)));
        }

        return $this->createResult(true);
    }
}
