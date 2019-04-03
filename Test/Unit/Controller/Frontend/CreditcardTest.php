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

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Calculation;
use Wirecard\ElasticEngine\Controller\Frontend\Cancel;
use Wirecard\ElasticEngine\Controller\Frontend\Creditcard;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;

use Magento\Payment\Model\MethodInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\TransactionService;

class CreditcardTest extends \PHPUnit_Framework_TestCase
{

    const ORDER_ID = '12345';
    const BASE_URL = 'https://example.com/';
    const CURRENCY_CODE = 'EUR';
    const TOTAL_AMOUNT = 15;

    /** @var Json */
    private $resultJson;

    /** @var Creditcard */
    private $controller;

    /** @var Session */
    private $checkoutSession;

    /** @var Data */
    private $paymentHelper;

    /** @var TransactionService */
    private $transactionServiceFactory;

    public function setUp()
    {

        $this->resultJson = $this->getMockBuilder(Json::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $resultJsonFactory = $this->getMockBuilder(JsonFactory::class)->disableOriginalConstructor()->getMock();
        $resultJsonFactory->expects($this->once())->method('create')->willReturn($this->resultJson);

        $this->transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);

        $this->checkoutSession = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $taxCalculation = $this->getMockBuilder(Calculation::class)->disableOriginalConstructor()->getMock();
        $resolver  = $this->getMockForAbstractClass(ResolverInterface::class);
        $storeManager = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $urlBuilder = $this->getMockForAbstractClass(UrlInterface::class);
        $this->paymentHelper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $methodConfig = $this->getMockForAbstractClass(ConfigInterface::class);

        $this->controller = new Creditcard($context, $resultJsonFactory, $this->transactionServiceFactory,
            $quoteRepository, $this->checkoutSession, $taxCalculation, $resolver, $storeManager, $urlBuilder,
            $this->paymentHelper, $methodConfig
        );
    }

    public function testExecuteWithEmptyCheckoutSession()
    {
        $expectedResultData = [
            'status' => 'ERR',
            'errMsg' => 'no quote found',
        ];

        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn(null);
        $this->resultJson->expects($this->once())->method('setData')->with($this->equalTo($expectedResultData));
        $this->controller->execute();
    }

    public function testExecuteWithFailedCreditCardUiFromBackend()
    {
        $quote = $this->getMockBuilder(Quote::class)->setMethods(['getBaseCurrencyCode', 'getGrandTotal', 'reserveOrderId', 'getReservedOrderId'])->disableOriginalConstructor()->getMock();
        $quote->expects($this->once())->method('reserveOrderId')->willReturn($quote);
        $quote->expects($this->once())->method('getReservedOrderId')->willReturn(self::ORDER_ID);
        $quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn(self::CURRENCY_CODE);
        $quote->expects($this->once())->method('getGrandTotal')->willReturn(self::TOTAL_AMOUNT);
        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn($quote);

        $method = $this->getMockForAbstractClass(MethodInterface::class);
        $method->expects($this->once())->method('getConfigData')->willReturn(self::BASE_URL);
        $this->paymentHelper->expects($this->once())->method('getMethodInstance')->wilLReturn($method);

        $creditCardConfig = $this->getMockBuilder(CreditCardConfig::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config->expects($this->once())->method('get')->willReturn($creditCardConfig);
        $transactionService = $this->getMockBuilder(TransactionService::class)->disableOriginalConstructor()->getMock();
        $transactionService->expects($this->once())->method('getConfig')->willReturn($config);
        $transactionService->expects($this->once())->method('getCreditCardUiWithData')->willReturn(null);
        $this->transactionServiceFactory->expects($this->once())->method('create')->willReturn($transactionService);

        $expectedResultData = [
            'status'  => 'ERR',
            'errMsg'  => 'cannot create UI',
            'details' => ['raw ui data' => null],
        ];
        $this->resultJson->expects($this->once())->method('setData')->with($this->equalTo($expectedResultData));

        $this->controller->execute();
    }

    public function testExecuteWithReceivedUiFromBackend()
    {
        $mockedUiJson = '{"foo":"bar"}';

        $quote = $this->getMockBuilder(Quote::class)->setMethods(['getBaseCurrencyCode', 'getGrandTotal', 'reserveOrderId', 'getReservedOrderId'])->disableOriginalConstructor()->getMock();
        $quote->expects($this->once())->method('reserveOrderId')->willReturn($quote);
        $quote->expects($this->once())->method('getReservedOrderId')->willReturn(self::ORDER_ID);
        $quote->expects($this->once())->method('getBaseCurrencyCode')->willReturn(self::CURRENCY_CODE);
        $quote->expects($this->once())->method('getGrandTotal')->willReturn(self::TOTAL_AMOUNT);
        $this->checkoutSession->expects($this->once())->method('getQuote')->willReturn($quote);

        $method = $this->getMockForAbstractClass(MethodInterface::class);
        $method->expects($this->once())->method('getConfigData')->willReturn(self::BASE_URL);
        $this->paymentHelper->expects($this->once())->method('getMethodInstance')->wilLReturn($method);

        $creditCardConfig = $this->getMockBuilder(CreditCardConfig::class)->disableOriginalConstructor()->getMock();
        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $config->expects($this->once())->method('get')->willReturn($creditCardConfig);
        $transactionService = $this->getMockBuilder(TransactionService::class)->disableOriginalConstructor()->getMock();
        $transactionService->expects($this->once())->method('getConfig')->willReturn($config);
        $transactionService->expects($this->once())->method('getCreditCardUiWithData')->willReturn($mockedUiJson);
        $this->transactionServiceFactory->expects($this->once())->method('create')->willReturn($transactionService);

        $expectedResultData = [
            'status'  => 'OK',
            'uiData'  => $mockedUiJson,
        ];
        $this->resultJson->expects($this->once())->method('setData')->with($this->equalTo($expectedResultData));

        $this->controller->execute();
    }

}