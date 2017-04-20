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

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Http\Client;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Http\Client\AuthorizationClient;
use Wirecard\ElasticEngine\Gateway\Http\TransactionServiceFactory;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\TransactionService;

class AuthorizationClientUTest extends \PHPUnit_Framework_TestCase
{
    const RESERVE = 'reserve';
    const FACTORY_CREATE = 'create';
    const GET_BODY = 'getBody';
    private $logger;

    private $urlBuilder;

    private $transactionService;

    private $responseArray;

    public function setUp()
    {
        $this->logger = $this->getMock(LoggerInterface::class);
        $this->urlBuilder = $this->getMock(UrlInterface::class);
        $this->transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();

        $this->responseArray = ['AMOUNT' => '1.0', 'CURRENCY' => 'EUR'];
    }

    public function testPlaceRequestWithRedirect()
    {
        $interactionResponse = $this->getMockBuilder(InteractionResponse::class)
            ->disableOriginalConstructor()->getMock();
        $interactionResponse->method('getRedirectUrl')->willReturn('http://redir.ect');

        /** @var \PHPUnit_Framework_MockObject_MockObject $transactionServiceMock */
        $transactionServiceMock = $this->transactionService;
        $transactionServiceMock->method(self::RESERVE)->willReturn($interactionResponse);

        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactory->method(self::FACTORY_CREATE)->willReturn($this->transactionService);

        $transfer = $this->getMock(TransferInterface::class);

        $transfer->method(self::GET_BODY)->willReturn($this->responseArray);

        /** @var TransactionServiceFactory $transactionServiceFactory */
        $client = new AuthorizationClient($this->logger, $this->urlBuilder, $transactionServiceFactory);

        $result = $client->placeRequest($transfer);

        $expected = array('redirect_url' => 'http://redir.ect');
        $this->assertEquals($expected, $result);
    }

    public function testPlaceRequestWithoutRedirect()
    {
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject $transactionServiceMock */
        $transactionServiceMock = $this->transactionService;
        $transactionServiceMock->method(self::RESERVE)->willReturn($response);

        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactory->method(self::FACTORY_CREATE)->willReturn($this->transactionService);

        $transfer = $this->getMock(TransferInterface::class);

        $transfer->method(self::GET_BODY)->willReturn($this->responseArray);

        /** @var TransactionServiceFactory $transactionServiceFactory */
        $client = new AuthorizationClient($this->logger, $this->urlBuilder, $transactionServiceFactory);

        $result = $client->placeRequest($transfer);

        $expected = array();
        $this->assertEquals($expected, $result);
    }

    public function testPlaceRequestReturnsException()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $transactionServiceMock */
        $transactionServiceMock = $this->transactionService;
        $transactionServiceMock->method(self::RESERVE)->willThrowException(new \Exception('message'));

        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactory->method(self::FACTORY_CREATE)->willReturn($this->transactionService);

        $transfer = $this->getMock(TransferInterface::class);

        $transfer->method(self::GET_BODY)->willReturn($this->responseArray);

        $expectedLogger = $this->getMock(LoggerInterface::class);
        $expectedLogger->expects($this->Once())->method('error')->with('message');

        /** @var TransactionServiceFactory $transactionServiceFactory */
        $client = new AuthorizationClient($expectedLogger, $this->urlBuilder, $transactionServiceFactory);

        $client->placeRequest($transfer);
    }
}
