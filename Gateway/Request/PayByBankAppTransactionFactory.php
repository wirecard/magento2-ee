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
 * Class PayByBankAppTransactionFactory
 * @package Wirecard\ElasticEngine\Gateway\Request
 */
class PayByBankAppTransactionFactory extends TransactionFactory
{
    const REFUND_OPERATION = Operation::CANCEL;

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

        $customFields = new CustomFieldCollection();
        $this->transaction->setCustomFields($customFields);
        $customFields->add(new CustomField('orderId', $this->orderId));

        $customFields->add($this->makeCustomField(
            'MerchantRtnStrng',
            $this->methodConfig->getValue('zapp_merchant_return_string')
        ));
        $customFields->add($this->makeCustomField('TxType', 'PAYMT'));
        $customFields->add($this->makeCustomField('DeliveryType', 'DELTAD'));

        $device = new Device($this->request->getServer('HTTP_USER_AGENT'));

        // fallback to a generic value if detection failed
        if ($device->getType() === null) {
            $device->setType('other');
        }

        if ($device->getOperatingSystem() === null) {
            $device->setOperatingSystem('other');
        }

        $this->transaction->setDevice($device);

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
}
