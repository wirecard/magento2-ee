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
use Wirecard\ElasticEngine\Gateway\Helper\ThreeDsHelper;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class used for creating Credit card transaction
 *
 * Class CreditCardTransactionFactory
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
     * @throws \Exception
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $commandSubject[self::PAYMENT];
        $this->transaction->setTokenId($paymentDO->getPayment()->getAdditionalInformation(
            CreditCardDataAssignObserver::TOKEN_ID
        ));

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', $this->orderId));

        if ($paymentDO->getPayment()->getAdditionalInformation(CreditCardDataAssignObserver::RECURRING)) {
            $this->transaction->setThreeD(false);
        }

        $challengeIndicator = $this->methodConfig->getValue('challenge_ind');
        $this->transaction = $this->threeDsHelper->getThreeDsTransaction(
            $challengeIndicator,
            $this->transaction,
            $paymentDO
        );

        $this->transaction->setCustomFields($customFields);

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
