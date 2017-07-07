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

namespace Wirecard\ElasticEngine\Test\Unit\Model\Ui;

use Magento\Framework\View\Asset\Repository;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Model\Ui\ConfigProvider;
use Wirecard\PaymentSdk\TransactionService;

class ConfigProviderUTest extends \PHPUnit_Framework_TestCase
{
    const LOGO_URL_PATH = '/logo/url.png';

    public function testGetConfigDummy()
    {
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn('/logo/url.png');

        $seamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        $transactionService->method('getDataForCreditCardUi')->willReturn(json_encode($seamlessRequestData));

        /**
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($transactionService);

        /**
         * @var $assetRepo Repository|\PHPUnit_Framework_MockObject_MockObject
         */
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn(self::LOGO_URL_PATH);

        $prov = new ConfigProvider($transactionServiceFactory, $assetRepo);
        $this->assertEquals([
            'payment' => [
                'wirecard_elasticengine_paypal' => [
                    'logo_url' => self::LOGO_URL_PATH
                ],
                'wirecard_elasticengine_creditcard' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData
                ],
                'wirecard_elasticengine_maestro' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData
                ]
            ]
        ], $prov->getConfig());
    }
}
