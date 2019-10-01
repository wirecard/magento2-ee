<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class TransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class TransactionFactory
{
    const PAYMENT = 'payment';
    const AMOUNT = 'amount';
    const REFUND_OPERATION = Operation::CREDIT;
    const CONFIG_KEY_SEND_ADDITIONAL = 'send_additional';
    const CONFIG_KEY_SEND_BASKET = 'send_shopping_basket';
    const FIELD_KEY_ORDER_ID = 'order_id';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var ConfigInterface
     */
    protected $methodConfig;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var AccountHolderFactory
     */
    protected $accountHolderFactory;

    /**
     * @var BasketFactory
     */
    protected $basketFactory;

    /**
     * @var Repository
     */
    protected $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * TransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param Transaction $transaction
     * @param ConfigInterface $methodConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountHolderFactory $accountHolderFactory
     * @param BasketFactory $basketFactory
     * @param Repository $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        Transaction $transaction,
        ConfigInterface $methodConfig,
        StoreManagerInterface $storeManager,
        AccountHolderFactory $accountHolderFactory,
        BasketFactory $basketFactory,
        Repository $transactionRepository = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        FilterBuilder $filterBuilder = null
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->resolver = $resolver;
        $this->transaction = $transaction;
        $this->methodConfig = $methodConfig;
        $this->storeManager = $storeManager;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->accountHolderFactory = $accountHolderFactory;
        $this->basketFactory = $basketFactory;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     *
     * @since 2.0.1 set order-number
     */
    public function create($commandSubject)
    {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment       = $commandSubject[self::PAYMENT];
        /** @var OrderAdapterInterface $order */
        $order         = $payment->getOrder();
        $cfgkey        = $this->transaction->getConfigKey();
        $this->orderId = $order->getOrderIncrementId();

        $this->addOrderIdToTransaction($this->orderId);
        $this->addBasicValuesToTransaction($order->getGrandTotalAmount(), $order->getCurrencyCode());
        $this->addRedirectUrlsToTransaction($cfgkey);

        if ($this->methodConfig->getValue(TransactionFactory::CONFIG_KEY_SEND_ADDITIONAL)) {
            $this->setAdditionalInformation($order);
        }

        return $this->transaction;
    }

    /**
     * @param $commandSubject
     * @return Transaction
     */
    public function capture($commandSubject)
    {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $paymentDo */
        $paymentDo = $commandSubject[self::PAYMENT];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDo->getOrder();

        /** @var Payment $payment */
        $payment = $paymentDo->getPayment();

        $this->orderId = $order->getId();
        $this->transaction->setParentTransactionId($payment->getParentTransactionId());

        $captureAmount = $commandSubject[self::AMOUNT];
        $this->addBasicValuesToTransaction($captureAmount, $order->getCurrencyCode());

        return $this->transaction;
    }

    /**
     * @param $commandSubject
     * @return Transaction
     */
    public function refund($commandSubject)
    {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $paymentDo */
        $paymentDo = $commandSubject[self::PAYMENT];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDo->getOrder();

        /** @var Payment $payment */
        $payment = $paymentDo->getPayment();

        $this->orderId = $order->getId();
        $this->transactionId = $payment->getParentTransactionId();
        $this->addBasicValuesToTransaction($commandSubject[self::AMOUNT], $order->getCurrencyCode());

        return $this->transaction;
    }

    public function void($commandSubject)
    {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $paymentDo */
        $paymentDo = $commandSubject[self::PAYMENT];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDo->getOrder();

        /** @var Payment $payment */
        $payment = $paymentDo->getPayment();

        $this->orderId = $order->getId();
        $this->transactionId = $payment->getParentTransactionId();
        $this->addBasicValuesToTransaction($order->getGrandTotalAmount(), $order->getCurrencyCode());
        $this->transaction->setParentTransactionId($this->transactionId);

        return $this->transaction;
    }

    /**
     * @return string
     */
    public function getRefundOperation()
    {
        return self::REFUND_OPERATION;
    }

    public function setAdditionalInformation($order)
    {
        $this->transaction->setDescriptor(sprintf(
            '%s %s',
            substr($this->storeManager->getStore()->getName(), 0, 9),
            $this->orderId
        ));
        $billingAddress = $order->getBillingAddress();
        $this->transaction->setAccountHolder($this->accountHolderFactory->create($billingAddress));
        if (null != $order->getShippingAddress()) {
            $this->transaction->setShipping($this->accountHolderFactory->create($order->getShippingAddress()));
        }

        if ($this->methodConfig->getValue(TransactionFactory::CONFIG_KEY_SEND_BASKET)) {
            $this->transaction->setBasket($this->basketFactory->create($order, $this->transaction));
        }
        $this->transaction->setIpAddress($order->getRemoteIp());
        $this->transaction->setConsumerId($order->getCustomerId());

        return $this->transaction;
    }

    /**
     * Gets all existing transactions for the specified orders
     *
     * @param $order
     * @param $payment
     * @return array
     */
    protected function getTransactionsForOrder($order, $payment)
    {
        if ($this->transactionRepository === null) {
            return [];
        }

        $filters[] = $this->filterBuilder->setField('payment_id')
            ->setValue($payment->getId())
            ->create();

        $filters[] = $this->filterBuilder->setField(TransactionFactory::FIELD_KEY_ORDER_ID)
            ->setValue($order->getId())
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
            ->create();

        return $this->transactionRepository->getList($searchCriteria)->toArray();
    }

    /**
     * Add mandatory order-number to transaction and custom field orderId for backwards compatibility
     *
     * @param $orderId
     * @since 2.0.1
     */
    protected function addOrderIdToTransaction($orderId)
    {
        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', $orderId));
        $this->transaction->setCustomFields($customFields);
        $this->transaction->setOrderNumber($orderId);
    }

    /**
     * Add default values to transaction
     * @param float $amount
     * @param string $currencyCode
     *
     * @since 2.2.1
     */
    protected function addBasicValuesToTransaction($amount, $currencyCode)
    {
        $amount = new Amount((float)$amount, $currencyCode);
        $this->transaction->setAmount($amount);
        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));
    }

    /**
     * Add redirect urls to transaction
     * @param $method
     *
     * @since 2.2.1
     */
    protected function addRedirectUrlsToTransaction($method)
    {
        $redirectUrl = $this->formatRedirectUrls($method, 'redirect');
        $cancelUrl   = $this->formatRedirectUrls($method, 'cancel');

        $this->transaction->setRedirect(new Redirect(
            $redirectUrl,
            $cancelUrl,
            $redirectUrl
        ));
    }

    /**
     * Format redirect urls
     *
     * @param $method
     * @param $type
     * @return string
     *
     * @since 2.2.1
     */
    protected function formatRedirectUrls($method, $type)
    {
        $method      = urlencode($method);
        $redirectUrl = sprintf(
            '%sfrontend/%s?method=%s',
            $this->urlBuilder->getRouteUrl('wirecard_elasticengine'),
            $type,
            $method
        );

        return $redirectUrl;
    }
}
