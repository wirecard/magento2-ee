<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\HTTP\ClientInterface;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Wirecard\ElasticEngine\Gateway\Model\RetreiveTransaction;
use Wirecard\PaymentSdk\Config\Config;

class RetreiveTransactionUTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RetreiveTransaction
     */
    protected $transaction;

    /**
     * @var ClientInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpClient;

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    public function setUp()
    {
        /** @var ClientFactory|PHPUnit_Framework_MockObject_MockObject $clientFactory */
        $clientFactory = $this->getMockWithoutInvokingTheOriginalConstructor(ClientFactory::class);

        $this->httpClient = $this->getMockWithoutInvokingTheOriginalConstructor(ClientInterface::class);
        $clientFactory->method('create')->willReturn($this->httpClient);

        $this->config = $this->getMockWithoutInvokingTheOriginalConstructor(Config::class);

        $this->transaction = new RetreiveTransaction(
            $clientFactory
        );
    }

    // by request id

    public function testByRequestId()
    {
        $this->httpClient->expects($this->once())->method('get')
            ->with('/engine/rest/merchants/maid/payments/search?payment.request-id=request-id');

        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('<xml/>');
        $res = $this->transaction->byRequestId($this->config, 'request-id', 'maid');
        $this->assertEquals('<xml/>', $res);
    }

    public function testByRequestIdNotFound()
    {
        $this->httpClient->method('getStatus')->willReturn(404);

        $res = $this->transaction->byRequestId($this->config, 'request-id', 'maid');
        $this->assertFalse($res);
    }

    public function testByRequestIdWithError()
    {
        $this->httpClient->method('getStatus')->willReturn(400);

        $res = $this->transaction->byRequestId($this->config, 'request-id', 'maid');
        $this->assertFalse($res);
    }

    // by transaction id

    public function testByTransactionId()
    {
        $this->httpClient->method('get')
            ->withConsecutive(
                ['/engine/rest/merchants/maid/payments/trid'],
                ['/engine/rest/merchants/maid/payments/?group_transaction_id=ptrid'],
                ['/engine/rest/merchants/maid/payments/search?payment.request-id=rid']
            );

        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            '{"payments":{"payment":[{"transaction-type":"authorization","request-id":"rid"}]}}',
            '<xml/>'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertEquals('<xml/>', $res);
    }

    public function testByTransactionIdTransactionTypeNotFound()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            '{"payments":{"payment":[{"transaction-type":"purchase","request-id":"rid"}]}}'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdTransactionNotFound()
    {
        $this->httpClient->method('getStatus')->willReturn(404);
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdTransactionWithMalformedJson()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdTransactionWithoutPaymentObject()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{"fooo":{"parent-transaction-id":"ptrid"}}');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdTransactionWithoutParentTransactionId()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{"payment":{"foo":"ptrid"}}');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    // group Transaction

    public function testByTransactionIdGroupTransactionNotFound()
    {
        $this->httpClient->method('getStatus')->willReturnOnConsecutiveCalls(200, 404);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            ''
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdGroupTransactionWithMalformedJson()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            ''
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdGroupTransactionWithoutPaymentsObject()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            '{"foo":{"payment":[{"transaction-type":"purchase","request-id":"rid"}]}}'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdGroupTransactionWithoutPaymentObject()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            '{"payments":{"foo":[{"transaction-type":"purchase","request-id":"rid"}]}}'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }

    public function testByTransactionIdGroupTransactionWithoutTransactionType()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{"payment":{"parent-transaction-id":"ptrid"}}',
            '{"payments":{"payment":[{"foo":"purchase","request-id":"rid"}]}}'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertFalse($res);
    }
}
