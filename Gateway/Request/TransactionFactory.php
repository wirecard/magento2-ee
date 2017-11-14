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
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction as MageTransaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Exception\UnsupportedOperationException;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class TransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class TransactionFactory
{
    const PAYMENT = 'payment';
    const AMOUNT = 'amount';

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
     * @var Repository $transactionRepository
     */
    protected $transactionRepository;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder $filterBuilder
     */
    protected $filterBuilder;

    /**
     * TransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param Transaction $transaction
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        Transaction $transaction
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->resolver = $resolver;
        $this->transaction = $transaction;
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

        return $this->transaction;
    }

    public function capture($commandSubject) {
        if (!isset($commandSubject[self::PAYMENT])
            || !$commandSubject[self::PAYMENT] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided.');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $commandSubject[self::PAYMENT];
        $this->orderId = $payment->getOrder()->getId();

        $paymentIdFilter = $this->filterBuilder->setField('payment_id')
            ->setValue($payment->getPayment()->getId())
            ->create();

        $orderIdFilter = $this->filterBuilder->setField('order_id')
            ->setValue($this->orderId)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter($paymentIdFilter)->addFilter($orderIdFilter)->create();

        /** @var Collection $transactionList */
        $transactionList = $this->transactionRepository->getList($searchCriteria);

        $parentTransactionId = array();
        foreach($transactionList as $transaction) {
            /** @var MageTransaction $transaction */
            if (!isset($parentTransactionId['id']) || $parentTransactionId['id'] < $transaction->getId()){
                $parentTransactionId = array('id' => $transaction->getId(), 'txn' => $transaction->getTxnId());
            }

            if ($transaction->getTxnType() === TransactionInterface::TYPE_CAPTURE) {
                throw new UnsupportedOperationException('Can not capture a capture.');
            }
        }

        $this->transaction->setParentTransactionId($parentTransactionId['txn']);
        $this->transaction->setEntryMode('ecommerce');
        $this->transaction->setLocale(substr($this->resolver->getLocale(), 0, 2));

        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecard_elasticengine');
        $this->transaction->setRedirect(new Redirect(
            $wdBaseUrl . 'frontend/redirect',
            $wdBaseUrl . 'frontend/cancel',
            $wdBaseUrl . 'frontend/redirect'));
        $this->transaction->setNotificationUrl($wdBaseUrl . 'frontend/notify');
    }
}
