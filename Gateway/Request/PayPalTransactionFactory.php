<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class PayPalTransactionFactory
 */
class PayPalTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::CANCEL;

    /**
     * @var PayPalTransaction
     */
    protected $transaction;

    /**
     * PayPalTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param Transaction $transaction
     * @param BasketFactory $basketFactory
     * @param AccountHolderFactory $accountHolderFactory
     * @param ConfigInterface $methodConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        Transaction $transaction,
        BasketFactory $basketFactory,
        AccountHolderFactory $accountHolderFactory,
        ConfigInterface $methodConfig
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
    }

    /**
     * @param array $commandSubject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws MandatoryFieldMissingException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @since 2.2.1 Remove Hardcoded shipping setter
     *              Add send_additional check
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        /** @var PaymentDataObjectInterface $payment */
        $payment = $commandSubject[self::PAYMENT];
        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        $this->transaction->setAccountHolder($this->accountHolderFactory->create($billingAddress));
        $this->transaction->setOrderNumber($this->orderId);
        $this->transaction->setOrderDetail(sprintf(
            '%s %s %s',
            $billingAddress->getEmail(),
            $billingAddress->getFirstname(),
            $billingAddress->getLastname()
        ));

        if ($this->methodConfig->getValue(TransactionFactory::CONFIG_KEY_SEND_BASKET)) {
            $this->transaction->setBasket($this->basketFactory->create($order, $this->transaction));
        }

        if ($this->methodConfig->getValue(TransactionFactory::CONFIG_KEY_SEND_ADDITIONAL)) {
            $this->setAdditionalInformation($order);
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

        $this->transaction->setParentTransactionId($this->transactionId);

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
