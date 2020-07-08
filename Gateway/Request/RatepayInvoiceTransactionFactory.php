<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
 * Class used for creating Ratepay invoice transaction
 *
 * Class RatepayInvoiceTransactionFactory
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
        parent::__construct(
            $urlBuilder,
            $resolver,
            $transaction,
            $methodConfig,
            $storeManager,
            $accountHolderFactory,
            $basketFactory
        );

        $this->checkoutSession = $session;
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     *
     * @since 2.2.1 Overwrite redirect urls for Ratepay mapping
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        // Special handling for ratepay name mapping
        $this->addRedirectUrlsToTransaction(RatepayInvoiceTransaction::NAME);

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
        $amount = new Amount((float)$commandSubject[self::AMOUNT], $order->getCurrencyCode());

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
        $amount = new Amount((float)$commandSubject[self::AMOUNT], $order->getCurrencyCode());

        $this->transaction->setParentTransactionId($this->transactionId);
        $this->transaction->setAmount($amount);
        $this->transaction->setBasket($this->basketFactory->refund($order, $this->transaction));

        return $this->transaction;
    }

    public function void($commandSubject)
    {
        parent::void($commandSubject);

        $payment = $commandSubject[self::PAYMENT];
        $order = $payment->getOrder();
        $this->transaction->setBasket($this->basketFactory->void($order, $this->transaction));

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
