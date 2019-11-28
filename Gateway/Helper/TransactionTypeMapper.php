<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Magento\Sales\Api\Data\TransactionInterface as MagentoTransactionInterface;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionType\Authorization;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionType\Cancel;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionType\Purchase;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionType\Refund;

/**
 * Class TransactionTypeMapper
 *
 * @since 3.0.0
 * @package Wirecard\ElasticEngine\Gateway\Helper
 */
class TransactionTypeMapper
{
    /** @var string */
    private $transactionType;

    /**
     * TransactionTypeMapper constructor.
     * @param string $transactionType
     * @since 3.0.0
     */
    public function __construct($transactionType)
    {
        $this->transactionType = $transactionType;
    }

    /**
     * Map TransactionTypeInterface to MagentoTransactionInterface type
     * @return string
     * @since 3.0.0
     */
    public function getMappedTransactionType()
    {
        if ($this->isTransactionType(Authorization::getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_AUTH;
        }

        if ($this->isTransactionType(Purchase::getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_CAPTURE;
        }

        if ($this->isTransactionType(Refund::getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_REFUND;
        }

        if ($this->isTransactionType(Cancel::getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_VOID;
        }

        if ($this->transactionType === 'check-payer-response') {
            return MagentoTransactionInterface::TYPE_PAYMENT;
        }

        return $this->transactionType;
    }

    /**
     * @param array $mappableTransactionTypes
     * @return bool
     * @since 3.0.0
     */
    private function isTransactionType($mappableTransactionTypes)
    {
        return in_array($this->transactionType, $mappableTransactionTypes);
    }
}
