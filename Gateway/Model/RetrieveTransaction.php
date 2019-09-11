<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Model;

use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\HTTP\ClientInterface;
use Wirecard\ElasticEngine\Gateway\Helper\NestedObject;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Transaction\Transaction;

/**
 * @since 2.1.0
 */
class RetrieveTransaction
{
    const URL_PAYMENT_BYREQUESTID_FMT = '%s/engine/rest/merchants/%s/payments/search?payment.request-id=%s';
    const URL_PAYMENTS_FMT = '%s/engine/rest/merchants/%s/payments/%s';
    const URL_PAYMENTS_BYTRANSACTIONID_FMT = '%s/engine/rest/merchants/%s/payments/?group_transaction_id=%s';

    const FIELD_PAYMENTS = 'payments';
    const FIELD_REQUEST_ID = 'request-id';

    const CONTENTTYPE_JSON = 'json';
    const CONTENTTYPE_XML = 'xml';

    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var NestedObject
     */
    protected $nestedObjectHelper;

    /**
     * PaymentStatus constructor.
     *
     * @param ClientFactory $httpClientFactory
     * @param NestedObject $nestedObjectHelper
     */
    public function __construct(ClientFactory $httpClientFactory, NestedObject $nestedObjectHelper)
    {
        $this->httpClientFactory  = $httpClientFactory;
        $this->nestedObjectHelper = $nestedObjectHelper;
    }

    /**
     * @param Config $config
     * @param string $requestId
     * @param string $maid we can not use the configured maid (maid might change during a 3ds fallback)
     *
     * @return string|null
     */
    public function byRequestId(Config $config, $requestId, $maid)
    {
        return $this->sendRequest($config,
            sprintf(self::URL_PAYMENT_BYREQUESTID_FMT, $config->getBaseUrl(), $maid, $requestId));
    }

    /**
     * query request-id by transactionId
     * get parent-transaction-id and fetch all payments for this id
     * take the request-id from the latest payment
     *
     * @param Config $config
     * @param string $transactionId
     * @param string $transactionType the transaction-type to be matched
     * @param string $maid
     *
     * @return string|null
     */
    public function byTransactionId(Config $config, $transactionId, $transactionType, $maid)
    {
        $data = $this->paymentByTransactionId($config, $transactionId, $maid);
        if (!is_object($data)) {
            return null;
        }

        $parentTransactionId = $this->nestedObjectHelper->getIn($data,
            [Transaction::PARAM_PAYMENT, Transaction::PARAM_PARENT_TRANSACTION_ID]);
        if (is_null($parentTransactionId)) {
            return null;
        }

        $payments = $this->paymentsByTransactionId($config, $parentTransactionId, $maid);
        if (!is_array($payments)) {
            return null;
        }

        $payment = $this->findTransactionByType($payments, $transactionType);
        if (is_null($payment)) {
            return null;
        }

        $requestId = $this->nestedObjectHelper->get($payment, self::FIELD_REQUEST_ID);
        if (is_null($requestId)) {
            return null;
        }

        return $this->byRequestId($config, $requestId, $maid);
    }

    /**
     * seek through payments result array for a matching transaction, search from behind
     *
     * @param $payments
     * @param $transactionType
     *
     * @return |null
     */
    protected function findTransactionByType($payments, $transactionType)
    {
        $payment = null;
        foreach (array_reverse($payments) as $p) {
            $paymentTransactionType = $this->nestedObjectHelper->get($p, Transaction::PARAM_TRANSACTION_TYPE);
            if (is_null($paymentTransactionType)) {
                continue;
            }

            if ($paymentTransactionType === $transactionType) {
                $payment = $p;
                break;
            }
        }

        return $payment;
    }

    /**
     * get payment by transaction-id
     * response contains parent transaction id
     *
     * @param Config $config
     * @param $transactionId
     * @param $maid
     *
     * @return object|null
     */
    protected function paymentByTransactionId(Config $config, $transactionId, $maid)
    {
        return $this->sendRequest($config,
            sprintf(self::URL_PAYMENTS_FMT, $config->getBaseUrl(), $maid, $transactionId), self::CONTENTTYPE_JSON);
    }

    /**
     * get payments by parent transaction-id
     *
     * @param Config $config
     * @param $transactionId
     * @param $maid
     *
     * @return array|null
     */
    protected function paymentsByTransactionId(Config $config, $transactionId, $maid)
    {
        $data = $this->sendRequest($config,
            sprintf(self::URL_PAYMENTS_BYTRANSACTIONID_FMT, $config->getBaseUrl(), $maid, $transactionId),
            self::CONTENTTYPE_JSON);

        if (!is_object($data)) {
            return null;
        }

        return $this->nestedObjectHelper->getIn($data, [self::FIELD_PAYMENTS, Transaction::PARAM_PAYMENT]);
    }

    /**
     * @param Config $config
     * @param string $url
     * @param string $contentType the accepted content-type
     *
     * @return string|object|null
     */
    protected function sendRequest(Config $config, $url, $contentType = self::CONTENTTYPE_XML)
    {
        $httpClient = $this->httpClientFactory->create();
        $httpClient->addHeader('Accept', 'application/' . $contentType);
        $httpClient->setCredentials($config->getHttpUser(), $config->getHttpPassword());
        $httpClient->get($url);

        return $this->checkResponse($httpClient, $contentType);
    }

    /**
     * We can not use the Magento\Framework\HTTP\ResponseFactory, it does not exists in magento < 2.3
     *
     * @param ClientInterface $httpClient
     * @param null $decodeAs
     *
     * @return null|string
     */
    protected function checkResponse(ClientInterface $httpClient, $decodeAs = null)
    {
        if ($httpClient->getStatus() == 404) {
            return null;
        }

        if ($httpClient->getStatus() < 200 || $httpClient->getStatus() > 299) {
            return null;
        }

        $body = $httpClient->getBody();
        if (!strlen($body)) {
            return null;
        }

        if ($decodeAs === self::CONTENTTYPE_JSON) {
            return json_decode($body);
        }

        return $body;
    }
}
