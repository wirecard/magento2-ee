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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class used for creating SEPA transaction
 */
class SepaTransactionFactory extends TransactionFactory
{
    /**
     * @var SepaDirectDebitTransaction
     */
    protected $transaction;

    /**
     * SepaTransactionFactory constructor.
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
     */
    public function create($commandSubject)
    {
        parent::create($commandSubject);

        /** @var PaymentDataObjectInterface $payment */
        $paymentDO = $commandSubject[self::PAYMENT];
        $additionalInfo = $paymentDO->getPayment()->getAdditionalInformation();

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($additionalInfo['accountFirstName']);
        $accountHolder->setLastName($additionalInfo['accountLastName']);
        $this->transaction->setAccountHolder($accountHolder);
        $this->transaction->setIban($additionalInfo['bankAccountIban']);

        if ($this->methodConfig->getValue('enable_bic')) {
            $this->transaction->setBic($additionalInfo['bankBic']);
        }
        $mandate = new Mandate($additionalInfo['mandateId']);
        $this->transaction->setMandate($mandate);

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
}
