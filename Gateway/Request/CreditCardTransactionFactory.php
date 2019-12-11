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
use Magento\Payment\Model\InfoInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\ElasticEngine\Gateway\Helper\ThreeDsHelper;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class CreditCardTransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class CreditCardTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::REFUND;

    /**
     * @var CreditCardTransaction
     */
    protected $transaction;

    /**
     * @var ThreeDsHelper
     */
    protected $threeDsHelper;

    /**
     * CreditCardTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param ResolverInterface $resolver
     * @param StoreManagerInterface $storeManager
     * @param Transaction $transaction
     * @param BasketFactory $basketFactory
     * @param AccountHolderFactory $accountHolderFactory
     * @param ConfigInterface $methodConfig
     * @param ThreeDsHelper $threeDsHelper
     *
     * @since 2.1.0 added ThreeDsHelper
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ResolverInterface $resolver,
        StoreManagerInterface $storeManager,
        Transaction $transaction,
        BasketFactory $basketFactory,
        AccountHolderFactory $accountHolderFactory,
        ConfigInterface $methodConfig,
        ThreeDsHelper $threeDsHelper
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

        $this->threeDsHelper = $threeDsHelper;
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

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO          = $commandSubject[self::PAYMENT];
        $paymentInformation = $paymentDO->getPayment();
        $challengeIndicator = $this->methodConfig->getValue('challenge_ind');

        $this->transaction->setTokenId($paymentInformation->getAdditionalInformation(
            CreditCardDataAssignObserver::TOKEN_ID
        ));

        $this->transaction = $this->threeDsHelper->getThreeDsTransaction(
            $challengeIndicator,
            $this->transaction,
            $paymentDO
        );

        if ($this->isRecurring($paymentInformation)) {
            $this->addRecurringParametersToTransaction();
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

    /**
     * Check if transaction is a recurring transaction
     *
     * @param InfoInterface $paymentInformation
     * @return mixed
     */
    private function isRecurring(InfoInterface $paymentInformation)
    {
        return $paymentInformation->getAdditionalInformation(CreditCardDataAssignObserver::RECURRING);
    }

    /**
     * Add parameters required for oneclick checkout
     */
    private function addRecurringParametersToTransaction()
    {
        $termUrl = $this->buildRedirectUrl(
            $this->urlBuilder->getRouteUrl('wirecard_elasticengine'),
            $this->transaction->getConfigKey()
        );

        //TODO: clarify why threeD is set to false on one click
        $this->transaction->setThreeD(false);
        $this->transaction->setTermUrl($termUrl);
    }

    //TODO: Move to helper class/trait since it is reused in other classes
    /**
     * @param $baseUrl
     * @param $suffix
     * @return string
     */
    public function buildRedirectUrl($baseUrl, $suffix)
    {
        $format = '%sfrontend/redirect?method=%s';
        return sprintf(
            $format,
            $baseUrl,
            urlencode($suffix)
        );
    }
}
