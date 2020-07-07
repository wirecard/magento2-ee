<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Model\Ui;

use Magento\Checkout\Model\Session;
use Magento\Directory\Model\Currency;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Model\Ui\ConfigProvider;
use Wirecard\PaymentSdk\Entity\IdealBic;
use Wirecard\PaymentSdk\TransactionService;

class ConfigProviderUTest extends \PHPUnit_Framework_TestCase
{
    const LOGO_URL_PATH = '/logo/url.png';
    const CREDITCARD_VAULT_CODE = 'wirecard_elasticengine_cc_vault';
    const WPP_URL = 'https://wpp-test.wirecard.com';

    public function testGetConfigDummyWithoutBic()
    {
        $assetRepo = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepo->method('getUrlWithParams')
            ->willReturn('/logo/url.png');

        $store = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getLocale')
            ->willReturn('en');

        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->method('getCode')
            ->willReturn('EUR');

        $storeModel = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeModel->method('getCurrentCurrency')
            ->willReturn($currency);

        $storeManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManager->method('getStore')
            ->willReturn($storeModel);

        $seamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionService->method('getDataForCreditCardUi')
            ->willReturn(json_encode($seamlessRequestData));

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
        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionServiceFactory->method('create')
            ->willReturn($transactionService);

        $methodInterface = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $methodInterface->method('getConfigData')
            ->will($this->onConsecutiveCalls(
                self::WPP_URL,
                false,
                false,
                self::WPP_URL
            ));
        $paymentHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentHelper->method('getMethodInstance')
            ->willReturn($methodInterface);
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var $assetRepo Repository|\PHPUnit_Framework_MockObject_MockObject
         */
        $assetRepo = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepo->method('getUrlWithParams')
            ->willReturn(self::LOGO_URL_PATH);

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

        $prov = new ConfigProvider(
            $transactionServiceFactory,
            $assetRepo,
            $paymentHelper,
            $session,
            $store,
            $storeManager
        );
        $this->assertEquals([
            'payment' => [
                'wirecard_elasticengine_paypal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_creditcard' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'vaultCode' => self::CREDITCARD_VAULT_CODE,
                    'wpp_url' => self::WPP_URL . '/loader/paymentPage.js'
                ],
                'wirecard_elasticengine_sepadirectdebit' => [
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
                'wirecard_elasticengine_giropay' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ratepayinvoice' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript,
                    'billing_equals_shipping' => false
                ],
                'wirecard_elasticengine_alipayxborder' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_poipia' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_paybybankapp' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ]
            ]
        ], $prov->getConfig());
    }

    public function testGetConfigDummyWithBic()
    {
        $assetRepo = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepo->method('getUrlWithParams')
            ->willReturn('/logo/url.png');

        $store = $this->getMockBuilder(Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $store->method('getLocale')
            ->willReturn('en');

        $currency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $currency->method('getCode')
            ->willReturn('EUR');

        $storeModel = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeModel->method('getCurrentCurrency')
            ->willReturn($currency);

        $storeManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManager->method('getStore')
            ->willReturn($storeModel);

        $seamlessRequestData = [
            'key' => 'value'
        ];
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionService->method('getDataForCreditCardUi')
            ->willReturn(json_encode($seamlessRequestData));

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
        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionServiceFactory->method('create')
            ->willReturn($transactionService);

        $methodInterface = $this->getMockBuilder(MethodInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $methodInterface->method('getConfigData')
            ->will($this->onConsecutiveCalls(
                self::WPP_URL,
                true,
                true,
                self::WPP_URL
            ));
        $paymentHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentHelper->method('getMethodInstance')
            ->willReturn($methodInterface);
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var $assetRepo Repository|\PHPUnit_Framework_MockObject_MockObject
         */
        $assetRepo = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepo->method('getUrlWithParams')
            ->willReturn(self::LOGO_URL_PATH);

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

        $prov = new ConfigProvider(
            $transactionServiceFactory,
            $assetRepo,
            $paymentHelper,
            $session,
            $store,
            $storeManager
        );
        $this->assertEquals([
            'payment' => [
                'wirecard_elasticengine_paypal' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_creditcard' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'vaultCode' => self::CREDITCARD_VAULT_CODE,
                    'wpp_url' => self::WPP_URL . '/loader/paymentPage.js'
                ],
                'wirecard_elasticengine_sepadirectdebit' => [
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
                'wirecard_elasticengine_giropay' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_ratepayinvoice' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ratepay_script' => $ratepayScript,
                    'billing_equals_shipping' => true
                ],
                'wirecard_elasticengine_alipayxborder' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_poipia' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ],
                'wirecard_elasticengine_paybybankapp' => [
                    'logo_url' => self::LOGO_URL_PATH,
                    'ideal_bic' => $idealBic
                ]
            ]
        ], $prov->getConfig());
    }
}
