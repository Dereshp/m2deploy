<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Api;

/**
 * Create order api
 * @api
 */
interface CreateOrderInterface
{
    /**
     * Create float order, get customer redirect url
     *
     * @param string $cartId
     * @return string
     */
    public function execute(string $cartId): string;
}
