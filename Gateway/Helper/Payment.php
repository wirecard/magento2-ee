<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Class Payment
 *
 * @since 2.1.0
 * @package Wirecard\ElasticEngine\Gateway\Helper
 */
class Payment
{
    /**
     * @var Order\Payment\Repository
     */
    private $paymentRepository;

    /**
     * @var Order\Payment\Transaction\Repository
     */
    private $paymentTransactionRepository;

    /**
     * Payment constructor.
     *
     * @param Order\Payment\Repository $paymentRepository
     * @param Order\Payment\Transaction\Repository $paymentTransactionRepository
     */
    public function __construct(
        Order\Payment\Repository $paymentRepository,
        Order\Payment\Transaction\Repository $paymentTransactionRepository
    ) {
        $this->paymentRepository            = $paymentRepository;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
    }

    /**
     * @param Order\Payment $payment
     * @param SuccessResponse|FormInteractionResponse|InteractionResponse $sdkResponse
     * @param bool $save whether payment and transaction should be saved to db
     * @param string $transactionIdPostfix optionally add postfix to the transaction-id
     *
     * @return Transaction|null
     * @throws LocalizedException
     */
    public function addTransaction($payment, $sdkResponse, $save = false, $transactionIdPostfix = '')
    {
        if ($sdkResponse instanceof FailureResponse) {
            return null;
        }
        $transactionId = $sdkResponse->getTransactionId() . $transactionIdPostfix;
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('transactionId', $transactionId);

        $data = $sdkResponse->getData();
        $data['has-notify'] = true;

        $payment->setAdditionalInformation($data);

        $additionalInfo = [];
        foreach ($data as $key => $value) {
            $additionalInfo[$key] = $value;
        }
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $additionalInfo);

        $transaction = $payment->addTransaction(TransactionInterface::TYPE_ORDER);

        if ($save) {
            $this->paymentRepository->save($payment);
            $this->paymentTransactionRepository->save($transaction);
        }

        return $transaction;
    }
}
