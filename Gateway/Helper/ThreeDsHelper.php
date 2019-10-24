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
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\AccountInfoFactory;
use Wirecard\ElasticEngine\Gateway\Validator\QuoteAddressValidator;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Constant\IsoTransactionType;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class ThreeDsHelper
 * @package Wirecard\ElasticEngine\Gateway\Helper
 */
class ThreeDsHelper
{
    /** @var AccountInfoFactory  */
    private $accountInfoFactory;

    /** @var AccountHolderFactory  */
    private $accountHolderFactory;

    /** @var AccountHolder */
    private $accountHolder;

    /** @var AccountHolder */
    private $shipping;

    /** @var string */
    private $token;

    /** @var QuoteAddressValidator */
    private $addressValidator;

    /**
     * ThreeDsHelper constructor.
     * @param AccountInfoFactory $accountInfoFactory
     * @param AccountHolderFactory $accountHolderFactory
     * @param QuoteAddressValidator $addressValidator
     *
     * @since 2.2.1 added QuoteAddressValidator
     */
    public function __construct(
        AccountInfoFactory $accountInfoFactory,
        AccountHolderFactory $accountHolderFactory,
        QuoteAddressValidator $addressValidator
    ) {
        $this->accountInfoFactory = $accountInfoFactory;
        $this->accountHolderFactory = $accountHolderFactory;
        $this->addressValidator = $addressValidator;
        $this->token = null;
    }

    /**
     * Create 3D Secure parameters based on OrderDto or PaymentDataObjectInterface
     * and adds them to existing transaction
     *
     * @param string $challengeIndicator
     * @param Transaction $transaction
     * @param OrderDto|PaymentDataObjectInterface $dataObject
     * @return Transaction
     * @since 2.1.0
     */
    public function getThreeDsTransaction($challengeIndicator, $transaction, $dataObject)
    {
        if ($dataObject instanceof OrderDto) {
            $this->setParametersForQuoteData($dataObject);
        }

        if ($dataObject instanceof PaymentDataObjectInterface) {
            $this->setParametersForPaymentData($dataObject);
        }

        $accountInfo = $this->accountInfoFactory->create($challengeIndicator, $this->token);
        if (!empty($this->accountHolder)) {
            $this->accountHolder->setAccountInfo($accountInfo);
            $transaction->setAccountHolder($this->accountHolder);
        }

        if (!empty($this->shipping)) {
            $transaction->setShipping($this->shipping);
        }
        $transaction->setIsoTransactionType(IsoTransactionType::GOODS_SERVICE_PURCHASE);

        return $transaction;
    }

    /**
     * Set accountHolder and shipping parameters based on OrderDto
     *
     * @param OrderDto $orderDto
     * @since 2.1.0
     */
    private function setParametersForQuoteData($orderDto)
    {
        $billingAddress = $orderDto->quote->getBillingAddress();
        $this->accountHolder = $this->fetchAccountHolder($billingAddress);
        $this->accountHolder->setCrmId($orderDto->quote->getCustomerId());
        $shippingAddress = $orderDto->quote->isVirtual() ? null : $orderDto->quote->getShippingAddress();
        if (isset($shippingAddress)) {
            $this->shipping = $this->fetchAccountHolder($shippingAddress);
        }
    }

    /**
     * Set accountHolder and shipping parameters based on PaymentDataObjectInterface
     *
     * @param PaymentDataObjectInterface $paymentDO
     * @since 2.1.0
     */
    private function setParametersForPaymentData($paymentDO)
    {
        $this->token = $paymentDO->getPayment()->getAdditionalInformation(CreditCardDataAssignObserver::TOKEN_ID);
        /** @var OrderAdapterInterface $order */
        $order = $paymentDO->getOrder();

        $billingAddress = $order->getBillingAddress();
        $this->accountHolder = $this->accountHolderFactory->create($billingAddress);
        $this->accountHolder->setCrmId($order->getCustomerId());

        $shippingAddress = $order->getShippingAddress();
        if (isset($shippingAddress)) {
            $this->shipping = $this->accountHolderFactory->create($shippingAddress);
        }
    }

    /**
     * Helper method to build the AccountHolder structure by an address
     *
     * @param \Magento\Quote\Model\Quote\Address $address Magento2 address from session
     * @return AccountHolder paymentSdk entity AccountHolder
     * @since 2.1.0 moved from CreditCard Controller
     */
    private function fetchAccountHolder($address)
    {
        $accountHolder = new AccountHolder();
        $accountHolder->setEmail($address->getEmail());
        $accountHolder->setPhone($address->getTelephone());

        if ($this->addressValidator->validate(['addressObject' => $address])) {
            $sdkAddress = new Address($address->getCountryId(), $address->getCity(), $address->getStreetLine(1));
            if (!empty($address->getStreetLine(2))) {
                $sdkAddress->setStreet2($address->getStreetLine(2));
            }
            $sdkAddress->setPostalCode($address->getPostcode());
            $accountHolder->setAddress($sdkAddress);
        }

        return $accountHolder;
    }
}
