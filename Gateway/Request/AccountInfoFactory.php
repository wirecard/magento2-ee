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
            $transactionsLastDay = $this->getCustomerTransactionCountForPeriod('-1 day');
            $transactionsLastYear = $this->getCustomerTransactionCountForPeriod('-1 year');
            //$this->setUserData();
        }
        $accountInfo->setChallengeInd($challengeIndicator);

        return $accountInfo;
    }

    private function setUserData() {
        // TODO Implement account info based on logged in user
        /** @var CustomerInterface $dataModel */
        $dataModel = $this->customerSession->getCustomerData();
        $created = $dataModel->getCreatedAt();
        $updated = $dataModel->getUpdatedAt();
        $addresses = $dataModel->getAddresses();
        foreach ($addresses as $address) {
            $addressId = $address->getId();
        }
        //customer login timestamp
    }

    /**
     * Create from-to date range array where from is set with date/time string
     *
     * @param string $startDateStatement
     * @return array
     */
    private function getDateRangeFilter($startDateStatement)
    {
        $endDate = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime($startDateStatement));
        $dateFilter = array('from'=>$startDate, 'to'=>$endDate);

        return $dateFilter;
    }

    private function getCustomerTransactionCountForPeriod($timePeriod)
    {
        $orderCollection = $this->orderCollection->create($this->customerSession->getCustomerId())
            ->addFieldToSelect('entity_id')
            ->addAttributeToFilter('created_at', $this->getDateRangeFilter($timePeriod));

        $orderIds = array_values($orderCollection->getAllIds());
        $transactionCount = $this->getTransactionCountForOrderIds($orderIds);

        return $transactionCount;
    }

    private function getTransactionCountForOrderIds($order_ids)
    {
        if ($this->transactionRepository === null) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $order_ids, 'IN')->create();
        $amountTransactions = $this->transactionRepository->getList($searchCriteria)->getTotalCount();

        return $amountTransactions;
    }
}
