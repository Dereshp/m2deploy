<?php
/**
 * Copyright © X2Y, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace X2Y\FloatPayments\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Mode options class
 *
 * Get payment mode options
 */
class Mode implements OptionSourceInterface
{
    const SANDBOX = 'sandbox';

    const PRODUCTION = 'production';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SANDBOX, 'label' => __('Sandbox')],
            ['value' => self::PRODUCTION, 'label' => __('Production')],
        ];
    }
}
