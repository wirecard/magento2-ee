<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment\Transaction as MageTransaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class CreditCardTransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class CreditCardTransactionFactory extends TransactionFactory
{
    /**
     * @var CreditCardTransaction
     */
    protected $transaction;

    /**
     * CreditCardTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param Transaction $transaction
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        Transaction $transaction,
        Repository $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        parent::__construct($urlBuilder, $resolver, $transaction);

        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        /** @var PaymentDataObjectInterface $payment */
        $paymentDO = $commandSubject[self::PAYMENT];

        $this->transaction->setTokenId($paymentDO->getPayment()->getAdditionalInformation(CreditCardDataAssignObserver::TOKEN_ID));

        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
        $this->transaction->setTermUrl($wdBaseUrl . 'frontend/redirect');

        return $this->transaction;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function capture($commandSubject)
    {
        parent::capture($commandSubject);

        return $this->transaction;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function refund($commandSubject)
    {
        parent::refund($commandSubject);

        /** @var PaymentDataObjectInterface $payment */
        $payment = $commandSubject[self::PAYMENT];
        $this->orderId = $payment->getOrder()->getId();

        $orderIdFilter = $this->filterBuilder->setField('order_id')
            ->setValue($this->orderId)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter($orderIdFilter)
            ->create();
        $tokenId = null;
        /** @var Collection $transactionList */
        $transactionList = $this->transactionRepository->getList($searchCriteria);
        /** @var MageTransaction $transaction */
        foreach ($transactionList as $transaction) {
            $tokenId = $transaction->getAdditionalInformation('raw_details_info')['creditCardToken'];
            if ($tokenId !== null) {
                break;
            }
        }

        if ($tokenId === null) {
            throw new MandatoryFieldMissingException("Credit card token is a mandatory field.");
        }

        $this->transaction->setTokenId($tokenId);

        $accountHolder = new AccountHolder();
        $accountHolder->setLastName('lastName');

        $this->transaction->setAccountHolder($accountHolder);

        return $this->transaction;
    }

}
