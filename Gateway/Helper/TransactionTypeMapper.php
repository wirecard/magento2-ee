<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Magento\Sales\Api\Data\TransactionInterface;
use Wirecard\PaymentSdk\Transaction\Transaction as SdkTransaction;

/**
 * Class TransactionTypeMapper
 *
 * @since 3.0.0
 * @package Wirecard\ElasticEngine\Gateway\Helper
 */
class TransactionTypeMapper
{
    private $type;

    public function __construct($transactionType)
    {
        $this->type = $transactionType;
    }

    /**
     * @param $transactionType
     * @throws \Exception
     */
    public function getMappedTransactionType()
    {
        return $this->getMagentoTransactionType();
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getMagentoTransactionType()
    {
        if ($this->isAuthorization()) {
            return TransactionInterface::TYPE_AUTH;
        }

        if ($this->isCapture()) {
            return TransactionInterface::TYPE_CAPTURE;
        }

        if ($this->isRefund()) {
            return TransactionInterface::TYPE_REFUND;
        }

        if ($this->isVoid()) {
            return TransactionInterface::TYPE_VOID;
        }

        if ($this->type === 'check-payer-response') {
            return TransactionInterface::TYPE_PAYMENT;
        }

        throw new \Exception("Unsupported Transaction Type!");
    }

    private function isAuthorization()
    {
        return in_array($this->type, [SdkTransaction::TYPE_AUTHORIZATION]);
    }

    private function isCapture()
    {
        return in_array(
            $this->type,
            [
                SdkTransaction::TYPE_DEPOSIT,
                SdkTransaction::TYPE_PURCHASE,
                SdkTransaction::TYPE_DEBIT,
                SdkTransaction::TYPE_CAPTURE_AUTHORIZATION
            ]
        );
    }

    private function isRefund()
    {
        return in_array(
            $this->type,
            [
                SdkTransaction::TYPE_REFUND_PURCHASE,
                SdkTransaction::TYPE_REFUND_DEBIT,
                SdkTransaction::TYPE_REFUND_CAPTURE,
                SdkTransaction::TYPE_CREDIT
            ]
        );
    }

    private function isVoid()
    {
        return in_array(
            $this->type,
            [
                SdkTransaction::TYPE_VOID_DEBIT,
                SdkTransaction::TYPE_VOID_PURCHASE,
                SdkTransaction::TYPE_VOID_AUTHORIZATION
            ]
        );
    }
}
