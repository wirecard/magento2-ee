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

/**
 * Class TransactionTypeMapper
 *
 * @since 2.2.2
 * @package Wirecard\ElasticEngine\Gateway\Helper
 */
class TransactionTypeMapper
{
    /**
     * @var GatewayTransactionTypeCollection
     */
    private $transactionTypeCollection;

    /**
     * TransactionTypeMapper constructor.
     * @param GatewayTransactionTypeCollection $transactionTypeCollection
     * @since 2.2.2
     */
    public function __construct(GatewayTransactionTypeCollection $transactionTypeCollection)
    {
        $this->transactionTypeCollection= $transactionTypeCollection;
    }

    /**
     * Map TransactionTypeInterface to MagentoTransactionInterface type
     * @param string $transactionType
     * @return string
     * @since 2.2.2
     */
    public function getMappedTransactionType($transactionType)
    {
        $mappedTransactionType = $transactionType;
        if (in_array($transactionType, $this->transactionTypeCollection->getAuthorizationTransactionTypes())) {
            $mappedTransactionType = MagentoTransactionInterface::TYPE_AUTH;
        }
        if (in_array($transactionType, $this->transactionTypeCollection->getPurchaseTransactionTypes())) {
            $mappedTransactionType = MagentoTransactionInterface::TYPE_CAPTURE;
        }
        if (in_array($transactionType, $this->transactionTypeCollection->getRefundTransactionTypes())) {
            $mappedTransactionType = MagentoTransactionInterface::TYPE_REFUND;
        }
        if (in_array($transactionType, $this->transactionTypeCollection->getCancelTransactionTypes())) {
            $mappedTransactionType = MagentoTransactionInterface::TYPE_VOID;
        }
        if ($transactionType === 'check-payer-response') {
            $mappedTransactionType = MagentoTransactionInterface::TYPE_PAYMENT;
        }

        return $mappedTransactionType;
    }
}
