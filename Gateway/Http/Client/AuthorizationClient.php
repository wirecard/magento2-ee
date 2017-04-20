<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Gateway\Http\Client;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;

/**
 * Class AuthorizationTransaction
 * @package Wirecard\ElasticEngine\Gateway\Http\Client
 */
class AuthorizationClient implements ClientInterface
{
    /**
     * @var ConfigInterface
     */
    private $eeConfig;

    /**
     * @var ConfigInterface
     */
    private $paypalConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * AuthorizationClient constructor.
     * @param ConfigInterface $eeConfig
     * @param ConfigInterface $paypalConfig
     * @param LoggerInterface $logger
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        ConfigInterface $eeConfig,
        ConfigInterface $paypalConfig,
        LoggerInterface $logger,
        UrlInterface $urlBuilder)
    {
        $this->eeConfig = $eeConfig;
        $this->paypalConfig = $paypalConfig;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $txConfig = new Config(
            $this->eeConfig->getValue('credentials/base_url'),
                $this->eeConfig->getValue('credentials/http_user'),
                $this->eeConfig->getValue('credentials/http_pass')
        );

        $paypalSdkConfig = new PaymentMethodConfig(
            PayPalTransaction::NAME,
            $this->paypalConfig->getValue('merchant_account_id'),
            $this->paypalConfig->getValue('secret')
        );
        $txConfig->add($paypalSdkConfig);

        $transactionService = new TransactionService($txConfig, $this->logger);

        $tx = $this->createTransaction($transferObject->getBody());

        try {
            $response = $transactionService->reserve($tx);
        }
        catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $response = null;
        }

        if ($response instanceof InteractionResponse) {
            return ['redirect_url' => $response->getRedirectUrl()];
        }

        return [];
    }

    /**
     * @param $data array|string
     * @return PayPalTransaction
     */
    private function createTransaction($data)
    {
        $wdBaseUrl = $this->urlBuilder->getRouteUrl('wirecardelasticengine');

        $tx = new PayPalTransaction();

        $tx->setAmount(new Amount(
            $data['AMOUNT'],
            $data['CURRENCY']
        ));

        $tx->setRedirect(new Redirect($wdBaseUrl.'checkout/back', $wdBaseUrl.'checkout/cancel'));
        $tx->setNotificationUrl($wdBaseUrl.'notify');

        return $tx;
    }
}
