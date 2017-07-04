<?php


namespace Wirecard\ElasticEngine\Observer;


use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class CreditCardDataAssignObserver extends AbstractDataAssignObserver
{
    const TOKEN_ID = 'token_id';

    /**
     * @param Observer $observer
     * @return void
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
    }

}