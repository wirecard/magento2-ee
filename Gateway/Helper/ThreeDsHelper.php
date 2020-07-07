<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Helper;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Quote\Model\Quote;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\AccountInfoFactory;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Constant\IsoTransactionType;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class ThreeDsHelper
 */
class ThreeDsHelper
{
    /** @var AccountInfoFactory */
    private $accountInfoFactory;

    /** @var AccountHolderFactory */
    private $accountHolderFactory;

    /**
     * ThreeDsHelper constructor.
     * @param AccountInfoFactory $accountInfoFactory
     * @param AccountHolderFactory $accountHolderFactory
     *
     * @since 2.2.1 added QuoteAddressValidator
     */
    public function __construct(
        AccountInfoFactory $accountInfoFactory,
        AccountHolderFactory $accountHolderFactory
    ) {
        $this->accountInfoFactory = $accountInfoFactory;
        $this->accountHolderFactory = $accountHolderFactory;
    }

    /**
     * Create 3D Secure parameters based on OrderDto or PaymentDataObjectInterface
     * and adds them to existing transaction
     *
     * @param string $challengeIndicator
     * @param Transaction $transaction
     * @param OrderDto|PaymentDataObjectInterface $dataObject
     * @return Transaction
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @since 2.1.0
     */
    public function getThreeDsTransaction($challengeIndicator, $transaction, $dataObject)
    {
        $accountInfo = $this->accountInfoFactory->create(
            $challengeIndicator,
            $this->getTokenForPaymentObject($dataObject)
        );
        /** @var Quote|OrderAdapterInterface $magentoQuoteOrder */
        $magentoQuoteOrder = $this->getDataObjectForQuoteOrOrder($dataObject);
        $accountHolder = $this->createAccountHolder($magentoQuoteOrder);
        $shipping = $this->createShipping($magentoQuoteOrder);

        if (!empty($accountHolder)) {
            $accountHolder->setAccountInfo($accountInfo);
            $transaction->setAccountHolder($accountHolder);
        }
        if (!empty($shipping)) {
            $transaction->setShipping($shipping);
        }
        $transaction->setIsoTransactionType(IsoTransactionType::GOODS_SERVICE_PURCHASE);

        return $transaction;
    }

    /**
     * @param Quote|OrderAdapterInterface $magentoQuoteOrder
     * @return AccountHolder
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @since 3.0.0
     */
    private function createAccountHolder($magentoQuoteOrder)
    {
        $billingAddress = $magentoQuoteOrder->getBillingAddress();
        $accountHolder = $this->accountHolderFactory->create($billingAddress);
        if ($magentoQuoteOrder->getCustomerId()) {
            $accountHolder->setCrmId((string)$magentoQuoteOrder->getCustomerId());
        }

        return $accountHolder;
    }

    /**
     * @param Quote|OrderAdapterInterface $magentoQuoteOrder
     * @return AccountHolder|null
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @since 3.0.0
     */
    private function createShipping($magentoQuoteOrder)
    {
        $shippingAddress = $magentoQuoteOrder->getShippingAddress();
        if (empty($shippingAddress)) {
            return null;
        }

        return $this->accountHolderFactory->create($shippingAddress);
    }

    /**
     * @param OrderDto|PaymentDataObjectInterface $dataContainer
     * @return mixed
     * @since 3.0.0
     */
    private function getTokenForPaymentObject($dataContainer)
    {
        if ($dataContainer instanceof PaymentDataObjectInterface) {
            return $dataContainer->getPayment()->getAdditionalInformation(CreditCardDataAssignObserver::TOKEN_ID);
        }
        return null;
    }

    /**
     * @param OrderDto|PaymentDataObjectInterface $dataContainer
     * @return Quote|OrderAdapterInterface
     * @since 3.0.0
     */
    private function getDataObjectForQuoteOrOrder($dataContainer)
    {
        if ($dataContainer instanceof PaymentDataObjectInterface) {
            return $dataContainer->getOrder();
        }
        return $dataContainer->quote;
    }
}
