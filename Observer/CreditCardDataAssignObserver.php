<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class CreditCardDataAssignObserver extends AbstractDataAssignObserver
{
    const TOKEN_ID = 'token_id';
    const VAULT_ENABLER = 'is_active_payment_token_enabler';
    const RECURRING = 'recurring_payment';

    /**
     * @param Observer $observer
     * @return void|null
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        if (array_key_exists(self::TOKEN_ID, $additionalData)) {
            $paymentInfo->setAdditionalInformation(
                self::TOKEN_ID,
                $additionalData[self::TOKEN_ID]
            );
        }

        if (array_key_exists(self::VAULT_ENABLER, $additionalData)) {
            $paymentInfo->setAdditionalInformation(
                self::VAULT_ENABLER,
                $additionalData[self::VAULT_ENABLER]
            );
        }

        if (array_key_exists(self::RECURRING, $additionalData)) {
            $paymentInfo->setAdditionalInformation(
                self::RECURRING,
                $additionalData[self::RECURRING]
            );
        }
    }
}
