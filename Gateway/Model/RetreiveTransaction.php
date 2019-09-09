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
use Wirecard\PaymentSdk\Config\Config;

/**
 * @since 2.1.0
 */
class RetreiveTransaction
{
    /**
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * PaymentStatus constructor.
     *
     * @param ClientFactory $httpClientFactory
     */
    public function __construct(ClientFactory $httpClientFactory)
    {
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * @param Config $config
     * @param string $requestId
     * @param string $maid we can not use the configured maid (maid might change during a 3ds fallback)
     *
     * @return string|false
     */
    public function byRequestId(Config $config, $requestId, $maid)
    {
        $urlFormat = '%s/engine/rest/merchants/%s/payments/search?payment.request-id=%s';

        $httpClient = $this->sendRequest($config, sprintf($urlFormat, $config->getBaseUrl(), $maid, $requestId));

        return $this->checkResponse($httpClient);
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
     * @return bool|ClientInterface
     */
    public function byTransactionId(Config $config, $transactionId, $transactionType, $maid)
    {
        $urlFormat  = '%s/engine/rest/merchants/%s/payments/%s';
        $httpClient = $this->sendRequest($config,
            sprintf($urlFormat, $config->getBaseUrl(), $maid, $transactionId), 'json');
        $body       = $this->checkResponse($httpClient);
        if ($body === false) {
            return false;
        }

        $data = json_decode($body);
        if (!is_object($data)) {
            return false;
        }

        if (!property_exists($data, 'payment') || !property_exists($data->payment, 'parent-transaction-id')) {
            return false;
        }

        $parentTransactionId = $data->payment->{'parent-transaction-id'};

        $urlFormat  = '%s/engine/rest/merchants/%s/payments/?group_transaction_id=%s';
        $httpClient = $this->sendRequest($config,
            sprintf($urlFormat, $config->getBaseUrl(), $maid, $parentTransactionId), 'json');
        $body       = $this->checkResponse($httpClient);
        if ($body === false) {
            return false;
        }

        $data = json_decode($body);
        if (!is_object($data)) {
            return false;
        }

        if (!property_exists($data, 'payments') || !property_exists($data->payments, 'payment')) {
            return false;
        }

        $payment = null;
        foreach (array_reverse($data->payments->payment) as $p) {
            if (!property_exists($p, 'transaction-type')) {
                continue;
            }

            if ($p->{'transaction-type'} === $transactionType) {
                $payment = $p;
                break;
            }
        }

        if ($payment === null) {
            return false;
        }

        $requestId = $payment->{'request-id'};

        return $this->byRequestId($config, $requestId, $maid);
    }

    /**
     * @param Config $config
     * @param string $url
     * @param string $type
     *
     * @return ClientInterface
     */
    protected function sendRequest(Config $config, $url, $type = 'xml')
    {
        $httpClient = $this->httpClientFactory->create();
        $httpClient->addHeader('Accept', 'application/' . $type);
        $httpClient->setCredentials($config->getHttpUser(), $config->getHttpPassword());
        $httpClient->get($url);

        return $httpClient;
    }

    /**
     * @param ClientInterface $httpClient
     *
     * @return bool|string
     */
    protected function checkResponse(ClientInterface $httpClient)
    {
        if ($httpClient->getStatus() == 404) {
            return false;
        }

        if ($httpClient->getStatus() < 200 || $httpClient->getStatus() > 299) {
            return false;
        }

        return $httpClient->getBody();
    }
}
