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
 * Class Authorization
 *
 * @since 3.0.0
 * @package Wirecard\ElasticEngine\Gateway\Helper\TransactionType
 */
class Authorization implements TransactionTypeInterface
{
    /**
     * @return array
     * @since 3.0.0
     */
    public function getTransactionTypes()
    {
        return [
            SdkTransaction::TYPE_AUTHORIZATION
        ];
    }
}
