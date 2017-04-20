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
use Wirecard\PaymentSdk\TransactionService;

class AuthorizationClientUTest extends \PHPUnit_Framework_TestCase
{
    public function testPlaceRequest()
    {
        $logger = $this->getMock(LoggerInterface::class);
        $urlBuilder = $this->getMock(UrlInterface::class);
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();
        $interactionResponse = $this->getMockBuilder(InteractionResponse::class)
            ->disableOriginalConstructor()->getMock();
        $interactionResponse->method('getRedirectUrl')->willReturn('http://redir.ect');
        $transactionService->method('reserve')->willReturn($interactionResponse);

        $transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactory->method('create')->willReturn($transactionService);

        $transfer = $this->getMock(TransferInterface::class);

        $transfer->method('getBody')->willReturn(['AMOUNT' => '1.0', 'CURRENCY' => 'EUR']);

        /** @var LoggerInterface $logger */
        /** @var UrlInterface $urlBuilder */
        /** @var TransactionServiceFactory $transactionServiceFactory */
        $client = new AuthorizationClient($logger, $urlBuilder, $transactionServiceFactory);

        $result = $client->placeRequest($transfer);

        $expected = array('redirect_url' => 'http://redir.ect');
        $this->assertEquals($expected, $result);
    }
}
