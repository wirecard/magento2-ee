<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper\TransactionType;

use Wirecard\PaymentSdk\Transaction\Transaction as SdkTransaction;

/**
 * Class Refund
 *
 * @since 2.2.2
 * @package Wirecard\ElasticEngine\Gateway\Helper\TransactionType
 */
class Refund implements TransactionTypeInterface
{
    /**
     * @return array
     * @since 2.2.2
     */
    public function getTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_REFUND_PURCHASE,
            SdkTransaction::TYPE_REFUND_DEBIT,
            SdkTransaction::TYPE_REFUND_CAPTURE,
            SdkTransaction::TYPE_CREDIT
        ];
    }
}
