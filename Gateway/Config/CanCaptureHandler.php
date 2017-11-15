<?php
/**
 * Created by IntelliJ IDEA.
 * User: jakub.polomsky
 * Date: 14. 11. 2017
 * Time: 17:58
 */

namespace Wirecard\ElasticEngine\Gateway\Config;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;

class CanCaptureHandler implements ValueHandlerInterface
{
    private $filterBuilder;
    private $searchCriteriaBuilder;
    private $transactionRepository;

    public function __construct(
        ObjectManager $objectManager,
        Payment\Transaction\Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function handle(array $subject, $storeId = null)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($subject);

        $order = $paymentDO->getOrder();

        $orderIdFilter = $this->filterBuilder->setField('order_id')
            ->setValue($order->getId())
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($orderIdFilter)
            ->create();

        /** @var Collection $transactionList */
        $transactionList = $this->transactionRepository->getList($searchCriteria);
        /** @var Transaction $transaction */
        $transaction = $transactionList->getItemById(max($transactionList->getAllIds()));

        return $transaction->getTxnType() == \Wirecard\PaymentSdk\Transaction\Transaction::TYPE_AUTHORIZATION;
    }
}
