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

use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class RatepayInvoiceTransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class RatepayInvoiceTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::CANCEL;

    /**
     * @var RatepayInvoiceTransaction
     */
    protected $transaction;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * RatepayInvoiceTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param Transaction $transaction
     * @param BasketFactory $basketFactory
     * @param AccountHolderFactory $accountHolderFactory
     * @param ConfigInterface $methodConfig
     * @param Session $session
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        Transaction $transaction,
        BasketFactory $basketFactory,
        AccountHolderFactory $accountHolderFactory,
        ConfigInterface $methodConfig,
        Session $session
    ) {
        parent::__construct($urlBuilder, $resolver, $transaction, $methodConfig, $storeManager, $accountHolderFactory, $basketFactory);

        $this->checkoutSession = $session;
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
        $payment = $commandSubject[self::PAYMENT];
        $additionalInfo = $payment->getPayment()->getAdditionalInformation();
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        $dob = $additionalInfo['customerDob'];
        $this->transaction->setAccountHolder($this->accountHolderFactory->create($billingAddress, $dob));
        $this->transaction->setOrderNumber($this->orderId);
        $this->transaction->setBasket($this->basketFactory->create($order, $this->transaction));

        if (strlen($this->checkoutSession->getData('invoiceDeviceIdent'))) {
            $deviceIdent = $this->checkoutSession->getData('invoiceDeviceIdent');
            $device = new \Wirecard\PaymentSdk\Entity\Device();
            $device->setFingerprint($deviceIdent);
            $this->transaction->setDevice($device);
            $this->checkoutSession->unsetData('invoiceDeviceIdent');
        }

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

        $payment = $commandSubject[self::PAYMENT];
        $order = $payment->getOrder();
        $amount = new Amount($commandSubject[self::AMOUNT], $order->getCurrencyCode());

        $this->transaction->setAmount($amount);
        $this->transaction->setBasket($this->basketFactory->capture($order, $this->transaction));

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

        $payment = $commandSubject[self::PAYMENT];
        $order = $payment->getOrder();
        $amount = new Amount($commandSubject[self::AMOUNT], $order->getCurrencyCode());

        $this->transaction->setParentTransactionId($this->transactionId);
        $this->transaction->setAmount($amount);
        $this->transaction->setBasket($this->basketFactory->refund($order, $this->transaction));

        return $this->transaction;
    }

    public function void($commandSubject)
    {
        parent::void($commandSubject);

        return $this->transaction;
    }

    /**
     * @return string
     */
    public function getRefundOperation()
    {
        return self::REFUND_OPERATION;
    }
}
