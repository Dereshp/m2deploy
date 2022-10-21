<?php
/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

declare(strict_types=1);

namespace X2Y\FloatPayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use X2Y\FloatPayments\Model\Spi\LoginInterface;

class LoginAfterPaymentSectionSave implements ObserverInterface
{
    /**
     * @var LoginInterface
     */
    private $login;

    /**
     * @param LoginInterface $login
     */
    public function __construct(LoginInterface $login)
    {
        $this->login = $login;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        try {
            $this->login->execute();
        } catch (\Exception $e) {
            return;
        }
    }
}
