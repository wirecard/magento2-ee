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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Vault\Model\ResourceModel\PaymentToken\Collection as VaultCollection;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Entity\AccountInfo;

/**
 * Class AccountInfoFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class AccountInfoFactory
{
    const PURCHASE_SUCCESS = [
        Order::STATE_PROCESSING,
        Order::STATE_CANCELED,
        Order::STATE_CLOSED,
        Order::STATE_COMPLETE
    ];

    protected $customerSession;
    protected $transactionRepository;
    protected $searchCriteriaBuilder;
    protected $logger;
    protected $orderCollection;
    protected $vaultCollection;

    public function __construct(CustomerSession $customerSession, LoggerInterface $logger, OrderCollection $orderCollection, Repository $transactionRepository, SearchCriteriaBuilder $searchCriteriaBuilder, VaultCollection $vaultCollection)
    {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderCollection = $orderCollection;
        $this->vaultCollection = $vaultCollection;
    }

    /**
     * @param string $challengeIndicator
     * @param string|null $token
     * @return AccountInfo
     */
    public function create($challengeIndicator, $token = null)
    {
        $accountInfo = new AccountInfo();
        $accountInfo->setAuthMethod(AuthMethod::GUEST_CHECKOUT);

        if ($this->customerSession->isLoggedIn()) {
            $accountInfo->setAuthMethod(AuthMethod::USER_CHECKOUT);
            $this->setUserData($accountInfo);
            $this->setCreditCardCreationDate($accountInfo, $token);
            $purchasesLastSixMonths = $this->getCustomerFinalOrderCountForPeriod('-6 months');
            $accountInfo->setAmountPurchasesLastSixMonths($purchasesLastSixMonths);
        }
        $accountInfo->setChallengeInd($challengeIndicator);

        return $accountInfo;
    }

    /**
     * @param AccountInfo $accountInfo
     */
    private function setUserData($accountInfo)
    {
        /** @var CustomerInterface $dataModel */
        $dataModel = $this->customerSession->getCustomerData();

        $accountInfo->setCreationDate($this->createDateWithFormat(
            $dataModel->getCreatedAt(),
            AccountInfo::DATE_FORMAT
        ));
        $accountInfo->setUpdateDate($this->createDateWithFormat(
            $dataModel->getCreatedAt(),
            AccountInfo::DATE_FORMAT
        ));
    }

    /**
     * Set card creation date for existing token
     *
     * @param AccountInfo $accountInfo
     * @param string $token
     * @since 2.1.0
     */
    private function setCreditCardCreationDate($accountInfo, $token)
    {
        if (!empty($token)) {
            $createdDates = $this->vaultCollection->addFieldToFilter('gateway_token', $token)
                ->getColumnValues('created_at');

            $creationDate = new \DateTime(reset($createdDates));
            $creationDate->format(AccountInfo::DATE_FORMAT);

            $accountInfo->setCardCreationDate($creationDate);
        }
    }

    /**
     * @param string $dateString
     * @param string $format
     * @return \DateTime
     * @since 2.1.0
     */
    private function createDateWithFormat($dateString, $format)
    {
        $date = new \DateTime($dateString);
        $date->format($format);

        return $date;
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
        $dateFilter = ['from'=>$startDate, 'to'=>$endDate];

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
        //@TODO use for transactionsLastDay + Year when clarified
        $this->orderCollection->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
            ->addFieldToFilter('created_at', $this->getDateRangeFilter($timePeriod));

        $orderIds = array_values($this->orderCollection->getAllIds());
        //@TODO use total count of orders for customer related transactions?
        //$transactionCount = $this->collection->getTotalCount();
        $transactionCount = $this->getTransactionCountForOrderIds($orderIds);

        return $transactionCount;
    }

    /**
     * Get orders with predefined order status for customer id with specific timeperiod
     * $timePeriod can be specified with relative datetime formats (e.g. 'yesterday' or '-1 day')
     *
     * @param string $timePeriod
     * @return int
     * @since 2.1.0
     */
    private function getCustomerFinalOrderCountForPeriod($timePeriod)
    {
        $this->orderCollection->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
            ->addFieldToFilter('created_at', $this->getDateRangeFilter($timePeriod))
            ->addFieldToFilter('status', ['in' => self::PURCHASE_SUCCESS]);
        $orderCount = $this->orderCollection->getTotalCount();

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
}
