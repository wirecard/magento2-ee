<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper\TransactionType;

/**
 * Interface TransactionTypeInterface
 *
 * @since 3.0.0
 * @package Wirecard\ElasticEngine\Gateway\Helper\TransactionType
 */
interface TransactionTypeInterface
{
    /**
     * @return array
     */
    public static function getTransactionTypes();
}
