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
    /**
     * @var Authorization
     */
    private $authorization;
    /**
     * @var Purchase
     */
    private $purchase;
    /**
     * @var Refund
     */
    private $refund;
    /**
     * @var Cancel
     */
    private $cancel;

    /**
     * TransactionTypeMapper constructor.
     * @param Authorization $authorization
     * @param Purchase $purchase
     * @param Refund $refund
     * @param Cancel $cancel
     * @since 3.0.0
     */
    public function __construct(
        Authorization $authorization,
        Purchase $purchase,
        Refund $refund,
        Cancel $cancel
    ) {
        $this->authorization = $authorization;
        $this->purchase = $purchase;
        $this->refund = $refund;
        $this->cancel = $cancel;
    }

    /**
     * Map TransactionTypeInterface to MagentoTransactionInterface type
     * @param string $transactionType
     * @return string
     * @since 3.0.0
     */
    public function getMappedTransactionType($transactionType)
    {
        if ($this->isTransactionType($this->authorization->getTransactionTypes(), $transactionType)) {
            return MagentoTransactionInterface::TYPE_AUTH;
        }

        if ($this->isTransactionType($this->purchase->getTransactionTypes(), $transactionType)) {
            return MagentoTransactionInterface::TYPE_CAPTURE;
        }

        if ($this->isTransactionType($this->refund->getTransactionTypes(), $transactionType)) {
            return MagentoTransactionInterface::TYPE_REFUND;
        }

        if ($this->isTransactionType($this->cancel->getTransactionTypes(), $transactionType)) {
            return MagentoTransactionInterface::TYPE_VOID;
        }

        if ($transactionType === 'check-payer-response') {
            return MagentoTransactionInterface::TYPE_PAYMENT;
        }

        return $transactionType;
    }

    /**
     * @param array $mappableTransactionTypes
     * @param string $transactionType
     * @return bool
     * @since 3.0.0
     */
    private function isTransactionType($mappableTransactionTypes, $transactionType)
    {
        return in_array($transactionType, $mappableTransactionTypes);
    }
}
