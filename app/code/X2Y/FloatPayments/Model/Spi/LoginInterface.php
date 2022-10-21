<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

namespace X2Y\FloatPayments\Model\Spi;

use X2Y\FloatPayments\Api\Data\LoginResultInterface;

/**
 * Login to Float
 * @spi
 */
interface LoginInterface
{
    /**
     * Login to Float system and get response data
     *
     * @return LoginResultInterface
     * @throws \Exception
     */
    public function execute(): LoginResultInterface;
}
