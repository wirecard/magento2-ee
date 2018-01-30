<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
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

use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Model\Ui\ConfigProvider;
use Wirecard\PaymentSdk\Entity\IdealBic;
use Wirecard\PaymentSdk\TransactionService;

class ConfigProviderUTest extends \PHPUnit_Framework_TestCase
{
    const LOGO_URL_PATH = '/logo/url.png';
    const CREDITCARD_VAULT_CODE = 'wirecard_elasticengine_cc_vault';

    public function testGetConfigDummyWithoutBic()
    {
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn('/logo/url.png');

        $store = $this->getMockWithoutInvokingTheOriginalConstructor(Resolver::class);
        $store->method('getLocale')->willReturn('en');

        $seamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        $transactionService->method('getDataForCreditCardUi')->willReturn(json_encode($seamlessRequestData));

        $upiSeamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService->method('getDataForUpiUi')->willReturn(json_encode($upiSeamlessRequestData));

        $idealBic = [
            ['key' => IdealBic::ABNANL2A, 'label' => 'ABN Amro Bank'],
            ['key' => IdealBic::ASNBNL21, 'label' => 'ASN Bank'],
            ['key' => IdealBic::BUNQNL2A, 'label' => 'bunq'],
            ['key' => IdealBic::INGBNL2A, 'label' => 'ING'],
            ['key' => IdealBic::KNABNL2H, 'label' => 'Knab'],
            ['key' => IdealBic::RABONL2U, 'label' => 'Rabobank'],
            ['key' => IdealBic::RGGINL21, 'label' => 'Regio Bank'],
            ['key' => IdealBic::SNSBNL2A, 'label' => 'SNS Bank'],
            ['key' => IdealBic::TRIONL2U, 'label' => 'Triodos Bank'],
            ['key' => IdealBic::FVLBNL22, 'label' => 'Van Lanschot Bankiers']
        ];

        /**
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($transactionService);

        $methodInterface = $this->getMockWithoutInvokingTheOriginalConstructor(MethodInterface::class);
        $methodInterface->method('getConfigData')->willReturn(false);
        $paymentHelper = $this->getMockWithoutInvokingTheOriginalConstructor(Data::class);
        $paymentHelper->method('getMethodInstance')->willReturn($methodInterface);
        $session = $this->getMockWithoutInvokingTheOriginalConstructor(Session::class);

        /**
         * @var $assetRepo Repository|\PHPUnit_Framework_MockObject_MockObject
         */
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn(self::LOGO_URL_PATH);

        $ratepayScript = '
        <script>
        var di = {t:\'\',v:\'WDWL\',l:\'Checkout\'};
        </script>
        <script type=\'text/javascript\' src=\'//d.ratepay.com//di.js\'></script>
        <noscript>
            <link rel=\'stylesheet\' type=\'text/css\' href=\'//d.ratepay.com/di.css?t=&v=WDWL&l=Checkout\'>
        </noscript>
        <object type=\'application/x-shockwave-flash\' data=\'//d.ratepay.com/WDWL/c.swf\' width=\'0\' height=\'0\'>
            <param name=\'movie\' value=\'//d.ratepay.com/WDWL/c.swf\' />
            <param name=\'flashvars\' value=\'t=&v=WDWL\'/>
            <param name=\'AllowScriptAccess\' value=\'always\'/>
        </object>';

        $prov = new ConfigProvider($transactionServiceFactory, $assetRepo, $paymentHelper, $session, $store);
        $this->assertEquals([
            'payment' => [
                'wirecard_elasticengine_paypal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_creditcard' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData,
                    'vaultCode' => self::CREDITCARD_VAULT_CODE
                ],
                'wirecard_elasticengine_maestro' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData
                ],
                'wirecard_elasticengine_sepa' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'enable_bic' => false
                ],
                'wirecard_elasticengine_sofortbanking' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ideal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ratepayinvoice' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript
                ],
                'wirecard_elasticengine_ratepayinstall' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript
                ],
                'wirecard_elasticengine_alipayxborder' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_poipia' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_masterpass' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_unionpayinternational' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $upiSeamlessRequestData
                ]
            ]
        ], $prov->getConfig());
    }

    public function testGetConfigDummyWithBic()
    {
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn('/logo/url.png');

        $store = $this->getMockWithoutInvokingTheOriginalConstructor(Resolver::class);
        $store->method('getLocale')->willReturn('en');

        $seamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        $transactionService->method('getDataForCreditCardUi')->willReturn(json_encode($seamlessRequestData));

        $upiSeamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService->method('getDataForUpiUi')->willReturn(json_encode($upiSeamlessRequestData));

        $idealBic = [
            ['key' => IdealBic::ABNANL2A, 'label' => 'ABN Amro Bank'],
            ['key' => IdealBic::ASNBNL21, 'label' => 'ASN Bank'],
            ['key' => IdealBic::BUNQNL2A, 'label' => 'bunq'],
            ['key' => IdealBic::INGBNL2A, 'label' => 'ING'],
            ['key' => IdealBic::KNABNL2H, 'label' => 'Knab'],
            ['key' => IdealBic::RABONL2U, 'label' => 'Rabobank'],
            ['key' => IdealBic::RGGINL21, 'label' => 'Regio Bank'],
            ['key' => IdealBic::SNSBNL2A, 'label' => 'SNS Bank'],
            ['key' => IdealBic::TRIONL2U, 'label' => 'Triodos Bank'],
            ['key' => IdealBic::FVLBNL22, 'label' => 'Van Lanschot Bankiers']
        ];

        /**
         * @var $transactionServiceFactory TransactionServiceFactory|\PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionServiceFactory->method('create')->willReturn($transactionService);

        $methodInterface = $this->getMockWithoutInvokingTheOriginalConstructor(MethodInterface::class);
        $methodInterface->method('getConfigData')->willReturn(true);
        $paymentHelper = $this->getMockWithoutInvokingTheOriginalConstructor(Data::class);
        $paymentHelper->method('getMethodInstance')->willReturn($methodInterface);
        $session = $this->getMockWithoutInvokingTheOriginalConstructor(Session::class);

        /**
         * @var $assetRepo Repository|\PHPUnit_Framework_MockObject_MockObject
         */
        $assetRepo = $this->getMockWithoutInvokingTheOriginalConstructor(Repository::class);
        $assetRepo->method('getUrlWithParams')->willReturn(self::LOGO_URL_PATH);

        $ratepayScript = '
        <script>
        var di = {t:\'\',v:\'WDWL\',l:\'Checkout\'};
        </script>
        <script type=\'text/javascript\' src=\'//d.ratepay.com//di.js\'></script>
        <noscript>
            <link rel=\'stylesheet\' type=\'text/css\' href=\'//d.ratepay.com/di.css?t=&v=WDWL&l=Checkout\'>
        </noscript>
        <object type=\'application/x-shockwave-flash\' data=\'//d.ratepay.com/WDWL/c.swf\' width=\'0\' height=\'0\'>
            <param name=\'movie\' value=\'//d.ratepay.com/WDWL/c.swf\' />
            <param name=\'flashvars\' value=\'t=&v=WDWL\'/>
            <param name=\'AllowScriptAccess\' value=\'always\'/>
        </object>';

        $prov = new ConfigProvider($transactionServiceFactory, $assetRepo, $paymentHelper, $session, $store);
        $this->assertEquals([
            'payment' => [
                'wirecard_elasticengine_paypal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_creditcard' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData,
                    'vaultCode' => self::CREDITCARD_VAULT_CODE
                ],
                'wirecard_elasticengine_maestro' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $seamlessRequestData
                ],
                'wirecard_elasticengine_sepa' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'enable_bic' => true
                ],
                'wirecard_elasticengine_sofortbanking' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ideal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ratepayinvoice' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript
                ],
                'wirecard_elasticengine_ratepayinstall' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript
                ],
                'wirecard_elasticengine_alipayxborder' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_poipia' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_masterpass' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_unionpayinternational' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'seamless_request_data' => $upiSeamlessRequestData
                ]
            ]
        ], $prov->getConfig());
    }
}
