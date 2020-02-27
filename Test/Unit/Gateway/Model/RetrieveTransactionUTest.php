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
use Wirecard\ElasticEngine\Gateway\Helper\NestedObject;
use Wirecard\ElasticEngine\Gateway\Model\RetrieveTransaction;
use Wirecard\PaymentSdk\Config\Config;

class RetrieveTransactionUTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var RetrieveTransaction
     */
    protected $transaction;

    /**
     * @var ClientInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpClient;

    /**
     * @var NestedObject|PHPUnit_Framework_MockObject_MockObject
     */
    protected $nestedObject;

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    public function setUp()
    {
        /** @var ClientFactory|PHPUnit_Framework_MockObject_MockObject $clientFactory */
        $clientFactory = $this->getMockBuilder(ClientFactory::class)->disableOriginalConstructor()->getMock();

        $this->httpClient = $this->getMockBuilder(ClientInterface::class)->disableOriginalConstructor()->getMock();
        $clientFactory->method('create')->willReturn($this->httpClient);

        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();

        $this->nestedObject = $this->getMockBuilder(NestedObject::class)->disableOriginalConstructor()->getMock();

        $this->transaction = new RetrieveTransaction(
            $clientFactory,
            $this->nestedObject
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
        $this->assertNull($res);
    }

    public function testByRequestIdWithError()
    {
        $this->httpClient->method('getStatus')->willReturn(400);

        $res = $this->transaction->byRequestId($this->config, 'request-id', 'maid');
        $this->assertNull($res);
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
            '{}',
            '{}',
            '<xml/>'
        );
        $this->nestedObject->method('getIn')->willReturnOnConsecutiveCalls(
            'ptrid',
            json_decode('[{"transaction-type":"authorization","request-id":"rid"}]')
        );
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            'authorization',
            'rid'
        );

        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertEquals('<xml/>', $res);
    }

    public function testByTransactionIdTransactionTypeNotFound()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{}');
        $this->nestedObject->method('getIn')->willReturnOnConsecutiveCalls(
            'ptrid',
            json_decode('[{"transaction-type":"authorization","request-id":"rid"}]')
        );
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            'purchase',
            'rid'
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdTransactionNotFound()
    {
        $this->httpClient->method('getStatus')->willReturn(404);
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdTransactionWithMalformedJson()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdTransactionWithoutPaymentObject()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{}');
        $this->nestedObject->method('getIn')->willReturnOnConsecutiveCalls(
            'ptrid',
            null
        );
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdTransactionWithoutParentTransactionId()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{}');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    // group Transaction

    public function testByTransactionIdGroupTransactionNotFound()
    {
        $this->httpClient->method('getStatus')->willReturnOnConsecutiveCalls(200, 404);
        $this->httpClient->method('getBody')->willReturn('{}');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdGroupTransactionWithMalformedJson()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturnOnConsecutiveCalls(
            '{}',
            'xxxxxx'
        );
        $this->nestedObject->method('getIn')->willReturn('ptrid');
        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testByTransactionIdGroupTransactionWithoutTransactionType()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{}');
        $this->nestedObject->method('getIn')->willReturnOnConsecutiveCalls(
            'ptrid',
            json_decode('[{"transaction-type":"authorization","request-id":"rid"}]')
        );
        $this->nestedObject->method('get')->willReturn(null);

        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }

    public function testbyTransactionIdWithoutRequestId()
    {
        $this->httpClient->method('getStatus')->willReturn(200);
        $this->httpClient->method('getBody')->willReturn('{}');
        $this->nestedObject->method('getIn')->willReturnOnConsecutiveCalls(
            'ptrid',
            json_decode('[{"transaction-type":"authorization","request-id":"rid"}]')
        );
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            'authorization',
            null
        );

        $res = $this->transaction->byTransactionId($this->config, 'trid', 'authorization', 'maid');
        $this->assertNull($res);
    }
}
