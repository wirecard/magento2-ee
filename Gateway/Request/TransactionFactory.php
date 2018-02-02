<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
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

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Operation;
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
     * TransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param Transaction $transaction
     * @param ConfigInterface $methodConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountHolderFactory $accountHolderFactory
     * @param BasketFactory $basketFactory
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        Transaction $transaction,
        ConfigInterface $methodConfig,
        StoreManagerInterface $storeManager,
        AccountHolderFactory $accountHolderFactory,
        BasketFactory $basketFactory
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->resolver = $resolver;
        $this->transaction = $transaction;
        $this->methodConfig = $methodConfig;
        $this->storeManager = $storeManager;
        $this->accountHolderFactory = $accountHolderFactory;
        $this->basketFactory = $basketFactory;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     */
    public function create($commandSubject)
    {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $commandSubject[self::PAYMENT];

        /** @var OrderAdapterInterface $order */
        $order = $payment->getOrder();

        $amount = new Amount($order->getGrandTotalAmount(), $order->getCurrencyCode());
        $this->transaction->setAmount($amount);

        $this->orderId = $order->getOrderIncrementId();
        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', $this->orderId));
        $this->transaction->setCustomFields($customFields);

        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');

        $this->transaction->setRedirect(new Redirect(
            $wdBaseUrl . 'frontend/redirect',
            $wdBaseUrl . 'frontend/cancel',
            $wdBaseUrl . 'frontend/redirect'));
        $this->transaction->setNotificationUrl($wdBaseUrl . 'frontend/notify');

        if ($this->methodConfig->getValue('send_additional')) {
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
        $captureAmount = $commandSubject[self::AMOUNT];
        $amount = new Amount($captureAmount, $order->getCurrencyCode());

        $this->transaction->setParentTransactionId($payment->getParentTransactionId());
        $this->transaction->setAmount($amount);

        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
        $this->transaction->setNotificationUrl($wdBaseUrl . 'frontend/notify');

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
        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));
        $this->transaction->setAmount(new Amount($commandSubject[self::AMOUNT], $order->getCurrencyCode()));

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
        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));
        $this->transaction->setAmount(new Amount($order->getGrandTotalAmount(), $order->getCurrencyCode()));
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
        $this->transaction->setDescriptor(sprintf('%s %s',
            substr($this->storeManager->getStore()->getName(), 0, 9),
            $this->orderId
        ));
        $billingAddress = $order->getBillingAddress();
        $this->transaction->setAccountHolder($this->accountHolderFactory->create($billingAddress));
        if (null != $order->getShippingAddress()) {
            $this->transaction->setShipping($this->accountHolderFactory->create($order->getShippingAddress()));
        }
        $this->transaction->setOrderNumber($this->orderId);
        $this->transaction->setBasket($this->basketFactory->create($order, $this->transaction));
        $this->transaction->setIpAddress($order->getRemoteIp());
        $this->transaction->setConsumerId($order->getCustomerId());

        return $this->transaction;
    }
}
