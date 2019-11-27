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
 * Class Cancel
 *
 * @since 3.0.0
 * @package Wirecard\ElasticEngine\Gateway\Helper\TransactionType
 */
class Cancel implements TransactionTypeInterface
{
    /**
     * @return array
     * @since 3.0.0
     */
    public static function getTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_VOID_DEBIT,
            SdkTransaction::TYPE_VOID_PURCHASE,
            SdkTransaction::TYPE_VOID_AUTHORIZATION
        ];
    }
}
