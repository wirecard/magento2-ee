<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Request;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Exception\MandatoryFieldMissingException;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayByBankAppTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * Class used for creating PayByBank transaction
 *
 * Class PayByBankAppTransactionFactory
 */
class PayByBankAppTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::CANCEL;
    const CONFIG_MERCHANT_RETURN_STRING = 'zapp_merchant_return_string';
    const PBBA_DEVICE_DEFAULT = 'other';
    const PBBA_MERCHANT_RETURN_STRING = 'MerchantRtnStrng';
    const PBBA_TRANSACTION_TYPE = 'TxType';
    const PBBA_TRANSACTION_TYPE_PAYMENT = 'PAYMT';
    const PBBA_DELIVERY_TYPE = 'DeliveryType';
    const PBBA_DELIVERY_TYPE_DEFAULT = 'DELTAD';

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var PayByBankAppTransaction
     */
    protected $transaction;

    /**
     * PayByBankAppTransactionFactory constructor.
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $httpRequest
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
        ConfigInterface $methodConfig,
        RequestInterface $httpRequest
    ) {
        $this->request = $httpRequest;
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

        $userAgent = $this->request->getServer('HTTP_USER_AGENT');
        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('orderId', $this->orderId));

        $this->transaction->setCustomFields($this->addMandatoryPaymentCustomFields($customFields));
        $this->transaction->setDevice($this->createDevice($userAgent));

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

        $customFields = new CustomFieldCollection();
        $this->transaction->setCustomFields($customFields);
        $customFields->add(new CustomField('orderId', $this->orderId));

        $customFields->add($this->makeCustomField('RefundReasonType', 'LATECONFIRMATION'));
        $customFields->add($this->makeCustomField('RefundMethod', 'BACS'));

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
     * make new customfield with my prefix
     *
     * @param $key
     * @param $value
     * @return CustomField
     */
    protected function makeCustomField($key, $value)
    {
        $customField = new CustomField($key, $value);
        $customField->setPrefix('zapp.in.');

        return $customField;
    }

    /**
     * @param CustomFieldCollection $customFields
     * @return CustomFieldCollection
     * @since 2.2.2
     */
    private function addMandatoryPaymentCustomFields($customFields)
    {
        $customFields->add($this->makeCustomField(
            self::PBBA_MERCHANT_RETURN_STRING,
            $this->fetchMerchantReturnString()
        ));
        $customFields->add($this->makeCustomField(self::PBBA_TRANSACTION_TYPE, self::PBBA_TRANSACTION_TYPE_PAYMENT));
        $customFields->add($this->makeCustomField(self::PBBA_DELIVERY_TYPE, self::PBBA_DELIVERY_TYPE_DEFAULT));

        return $customFields;
    }

    /**
     * @param string $userAgent
     * @return Device
     * @since 2.2.2
     */
    private function createDevice($userAgent)
    {
        $device = new Device($userAgent);

        if ($device->getType() === null) {
            $device->setType(self::PBBA_DEVICE_DEFAULT);
        }

        if ($device->getOperatingSystem() === null) {
            $device->setOperatingSystem(self::PBBA_DEVICE_DEFAULT);
        }
        return $device;
    }

    /**
     * @return string
     * @since 2.2.2
     */
    private function fetchMerchantReturnString()
    {
        $customMerchantReturnString = $this->methodConfig->getValue(self::CONFIG_MERCHANT_RETURN_STRING);
        if (empty($customMerchantReturnString)) {
            $customMerchantReturnString = $this->formatRedirectUrls($this->transaction->getConfigKey(), 'redirect');
        }

        return $customMerchantReturnString;
    }
}
