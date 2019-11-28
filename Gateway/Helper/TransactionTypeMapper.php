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
 * @since 2.2.2
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
     * @since 2.2.2
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
     * @since 2.2.2
     */
    public function getMappedTransactionType($transactionType)
    {
        if (in_array($transactionType, $this->authorization->getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_AUTH;
        }

        if (in_array($transactionType, $this->purchase->getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_CAPTURE;
        }

        if (in_array($transactionType, $this->refund->getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_REFUND;
        }

        if (in_array($transactionType, $this->cancel->getTransactionTypes())) {
            return MagentoTransactionInterface::TYPE_VOID;
        }

        if ($transactionType === 'check-payer-response') {
            return MagentoTransactionInterface::TYPE_PAYMENT;
        }

        return $transactionType;
    }
}
