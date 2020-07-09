<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Wirecard\PaymentSdk\Transaction\Transaction as SdkTransaction;

/**
 * Collection of transaction types
 *
 * @since 2.2.2
 */
class SdkTransactionTypeCollection
{
    /**
     * @return array
     * @since 2.2.2
     */
    public function getAuthorizationTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_AUTHORIZATION
        ];
    }

    /**
     * @return array
     * @since 2.2.2
     */
    public function getPurchaseTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_DEPOSIT,
            SdkTransaction::TYPE_PURCHASE,
            SdkTransaction::TYPE_DEBIT,
            SdkTransaction::TYPE_CAPTURE_AUTHORIZATION
        ];
    }

    /**
     * @return array
     * @since 2.2.2
     */
    public function getCancelTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_VOID_DEBIT,
            SdkTransaction::TYPE_VOID_PURCHASE,
            SdkTransaction::TYPE_VOID_AUTHORIZATION
        ];
    }

    /**
     * @return array
     * @since 2.2.2
     */
    public function getRefundTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_REFUND_PURCHASE,
            SdkTransaction::TYPE_REFUND_DEBIT,
            SdkTransaction::TYPE_REFUND_CAPTURE,
            SdkTransaction::TYPE_CREDIT
        ];
    }
}
