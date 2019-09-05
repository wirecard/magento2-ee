<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Model\Adminhtml\Source\ChallengeIndicator;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AccountInfo;

/**
 * Class AccountInfoFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AccountInfoFactory
{
    protected $customerSession;
    protected $transactionRepository;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $orderCollection;
    protected $logger;

    public function __construct(CustomerSession $customerSession, LoggerInterface $logger, CollectionFactory $orderCollection, Repository $transactionRepository, FilterBuilder $filterBuilder, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderCollection = $orderCollection;
    }

    /**
     * @param ChallengeIndicator $challengeIndicator
     * @return AccountInfo
     */
    public function create($challengeIndicator)
    {
        $accountInfo = new AccountInfo();
        $accountInfo->setAuthMethod(AuthMethod::GUEST_CHECKOUT);

        if ($this->customerSession->isLoggedIn()) {
            $accountInfo->setAuthMethod(AuthMethod::USER_CHECKOUT);
            $this->setUserData($accountInfo);

            // @TODO clarify if transactions should be exchanged with orders due to customer based init
            $transactionsLastDay = $this->getCustomerTransactionCountForPeriod('-1 day');
            $transactionsLastYear = $this->getCustomerTransactionCountForPeriod('-1 year');

            $purchasesLastMonths = $this->getCustomerFinalOrderCountForPeriod('-6 months');
        }
        $accountInfo->setChallengeInd($challengeIndicator);

        return $accountInfo;
    }

    /**
     * @param AccountInfo $accountInfo
     */
    private function setUserData($accountInfo) {
        // @TODO Implement account info based on logged in user
        /** @var CustomerInterface $dataModel */
        $dataModel = $this->customerSession->getCustomerData();
        // @TODO Improve...
        $customerCreated = new \DateTime($dataModel->getCreatedAt());
        $customerCreated->format(AccountInfo::DATE_FORMAT);
        $customerUpdated = new \DateTime($dataModel->getUpdatedAt());
        $customerUpdated->format(AccountInfo::DATE_FORMAT);

        $accountInfo->setCreationDate($customerCreated);
        $accountInfo->setUpdateDate($customerUpdated);
    }

    /**
     * Create from-to date range array where from is set with datetime string
     * $startDateStatement can be specified with relative datetime formats (e.g. 'yesterday' or '-1 day')
     *
     * @param string $startDateStatement
     * @return array
     * @since 2.1.0
     */
    private function getDateRangeFilter($startDateStatement)
    {
        $endDate = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime($startDateStatement));
        $dateFilter = array('from'=>$startDate, 'to'=>$endDate);

        return $dateFilter;
    }

    /**
     * Get number of transactions based on customer and specific time period
     * $timePeriod can be specified with relative datetime formats (e.g. 'yesterday' or '-1 day')
     *
     * @param string $timePeriod
     * @return int
     * @since 2.1.0
     */
    private function getCustomerTransactionCountForPeriod($timePeriod)
    {
        $orderCollection = $this->orderCollection->create($this->customerSession->getCustomerId())
            ->addFieldToSelect('entity_id')
            ->addAttributeToFilter('created_at', $this->getDateRangeFilter($timePeriod));

        $orderIds = array_values($orderCollection->getAllIds());
        //@TODO use total count of orders for customer related transactions?
        //$transactionCount = $orderCollection->getTotalCount();
        $transactionCount = $this->getTransactionCountForOrderIds($orderIds);

        return $transactionCount;
    }

    // @TODO testing filter and searchcriteria
    private function getCustomerFinalOrderCountForPeriod($timePeriod)
    {
        $orderCollection = $this->orderCollection->create($this->customerSession->getCustomerId())
            ->addFieldToSelect('entity_id')
            ->addAttributeToFilter('created_at', $this->getDateRangeFilter($timePeriod))
            ->setSearchCriteria($this->getOrdersBasedOnStatus());

        $orderCount = $orderCollection->getTotalCount();

        return $orderCount;
    }

    /**
     * Get number of transactions based on order_id(s)
     *
     * @param array $order_ids
     * @return int
     * @since 2.1.0
     */
    private function getTransactionCountForOrderIds($order_ids)
    {
        if ($this->transactionRepository === null || empty($order_ids)) {
            return 0;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $order_ids, 'IN')->create();
        $amountTransactions = $this->transactionRepository->getList($searchCriteria)->getTotalCount();

        return $amountTransactions;
    }

    // @TODO check if searchCriteria behaves correct
    private function getOrdersBasedOnStatus()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('status', array('processing', 'canceled', 'closed', 'complete'), 'IN')->create();
        return $searchCriteria;
    }
}
