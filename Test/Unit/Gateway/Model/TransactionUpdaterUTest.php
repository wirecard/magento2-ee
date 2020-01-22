<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Exception;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use stdClass;
use Wirecard\ElasticEngine\Gateway\Helper\NestedObject;
use Wirecard\ElasticEngine\Gateway\Model\Notify;
use Wirecard\ElasticEngine\Gateway\Model\RetrieveTransaction;
use Wirecard\ElasticEngine\Gateway\Model\TransactionUpdater;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\AlipayCrossborderTransaction;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;

class TransactionUpdaterUTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var TransactionUpdater
     */
    protected $updater;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var Collection|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionCollection;

    /**
     * @var TransactionRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionRepository;

    /**
     * @var Transaction|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transaction;

    /**
     * @var RetrieveTransaction|PHPUnit_Framework_MockObject_MockObject
     */
    protected $retreiveTransaction;

    /**
     * @var TransactionServiceFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $transactionServiceFactory;

    /**
     * @var Config|PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var Notify|PHPUnit_Framework_MockObject_MockObject
     */
    protected $notify;

    /**
     * @var NestedObject|PHPUnit_Framework_MockObject_MockObject
     */
    protected $nestedObject;

    public function setUp()
    {
        $this->transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)->disableOriginalConstructor()->getMock();
        $transactionService              = $this->getMockBuilder(TransactionService::class)->disableOriginalConstructor()->getMock();
        $this->config                    = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $transactionService->method('getConfig')->willReturn($this->config);
        $this->transactionServiceFactory->method('create')->willReturn($transactionService);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->transactionCollection = $this->getMockBuilder(Collection::class)->disableOriginalConstructor()->getMock();

        $this->transactionRepository = $this->getMockBuilder(TransactionRepository::class)->disableOriginalConstructor()->getMock();
        $this->transaction           = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $this->transactionRepository->method('get')->willReturn($this->transaction);

        $this->retreiveTransaction = $this->getMockBuilder(RetrieveTransaction::class)->disableOriginalConstructor()->getMock();

        $this->notify = $this->getMockBuilder(Notify::class)->disableOriginalConstructor()->getMock();

        $this->nestedObject = $this->getMockBuilder(NestedObject::class)->disableOriginalConstructor()->getMock();

        $this->updater = new TransactionUpdater(
            $this->logger,
            $this->transactionServiceFactory,
            $this->transactionCollection,
            $this->transactionRepository,
            $this->retreiveTransaction,
            $this->notify,
            $this->nestedObject
        );
    }

    public function testRun()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "request-id"             => "rid",
                "transaction-type"       => "ttype",
                "transaction-id"         => "tid",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->nestedObject->method('get')->willReturnCallback(function ($object, $param) use ($rawData) {
            return $object->$param;
        });

        $this->retreiveTransaction->method('byRequestId')->willReturn('<xml/>');

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $sdkResponse */
        $sdkResponse = $this->getMockBuilder(SuccessResponse::class)
            ->disableOriginalConstructor()
            ->setMockClassName('SuccessResponse')
            ->getMock();

        $this->notify->method('fromXmlResponse')->with('<xml/>')->willReturn($sdkResponse);

        $this->transactionCollection->method('fetchItem')->willReturnOnConsecutiveCalls(
            $this->transaction,
            false
        );

        $this->logger->expects($this->at(1))->method('debug')
            ->with('WirecardTransactionUpdater::transaction: order: Notification response is instance of: SuccessResponse');

        $this->updater->run();
    }

    public function testRunWithoutRawData()
    {
        $this->transactionCollection->method('fetchItem')->willReturnOnConsecutiveCalls(
            $this->transaction,
            false
        );

        $this->updater->run();
    }

    public function testRunWithException()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-method"      => "creditcard",
                "request-id"          => "rid",
                "transaction-type"    => "ttype",
                "transaction-id"      => "tid",
                "merchant-account-id" => "maid"
            ]
        ];

        $this->transaction->method('getData')->willReturn(json_encode($rawData));
        $this->retreiveTransaction->method('byRequestId')->willThrowException(new Exception('foo'));
        $this->nestedObject->method('get')->willReturnCallback(function ($object, $param) {
            return $object->$param;
        });

        $this->notify->expects($this->never())->method('process');
        $this->transactionCollection->method('fetchItem')->willReturnOnConsecutiveCalls(
            $this->transaction,
            false
        );
        $this->logger->expects($this->once())->method('error');

        $this->updater->run();
    }

    // fetchNotify

    public function testFetchNotifyWithoutRawDetails()
    {
        $this->transaction->method('getData')->willReturn("{}");

        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithAltMethodName()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            null,
            'creditcard',
            'rid',
            'tid',
            'ttype',
            'maid'
        );

        $this->retreiveTransaction->method('byRequestId')->with($this->config, 'rid', 'maid');
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyWithoutMethodName()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            null,
            null,
            'rid',
            'tid',
            'ttype',
            'maid'
        );
        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutRequestId()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'creditcard',
            null,
            'tid',
            'ttype',
            'maid'
        );
        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutTransactionId()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'creditcard',
            'rid',
            null,
            'ttype',
            'maid'
        );
        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutTransactionType()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'creditcard',
            'rid',
            'tid',
            null,
            'maid'
        );
        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutMaid()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'creditcard',
            'rid',
            'tid',
            'ttype',
            null
        );
        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyByTransactionId()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "request-id"             => "rid",
                "transaction-id"         => "tid",
                "transaction-type"       => "ttype",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));
        $this->nestedObject->method('get')->willReturnCallback(function ($object, $param) {
            return $object->$param;
        });

        $this->retreiveTransaction->method('byRequestId')->willReturn(null);

        $this->retreiveTransaction->method('byTransactionId')
            ->with($this->config, "tid", "ttype", "maid")
            ->willReturn('<xml/>');
        $response = $this->getMockBuilder(SuccessResponse::class)->disableOriginalConstructor()->getMock();
        $this->notify->method('fromXmlResponse')->with('<xml/>')->willReturn($response);
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyNothingFound()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'creditcard',
            'rid',
            'tid',
            'ttype',
            'maid'
        );

        $this->retreiveTransaction->method('byRequestId')->willReturn(null);
        $this->retreiveTransaction->method('byTransactionId')->willReturn(null);

        $this->logger->method('debug')->with('WirecardTransactionUpdater::transaction: order: no notify found');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyAlipay()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            AlipayCrossborderTransaction::NAME,
            'rid-get-url',
            'tid',
            'ttype',
            'maid'
        );
        $this->retreiveTransaction->method('byRequestId')
            ->with($this->config, "rid", "maid")
            ->willReturn('<xml/>');

        $response = $this->getMockBuilder(SuccessResponse::class)->disableOriginalConstructor()->getMock();
        $this->notify->method('fromXmlResponse')->with('<xml/>')->willReturn($response);
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyRatepayInvoice()
    {
        $this->transaction->method('getData')->willReturn('{}');
        $this->nestedObject->method('get')->willReturnOnConsecutiveCalls(
            new stdClass(),
            'ratepay-invoice',
            'rid',
            'tid',
            'ttype',
            'maid'
        );
        $this->retreiveTransaction->method('byRequestId')
            ->with($this->config, "rid", "maid")
            ->willReturn('<xml/>');

        $this->transactionServiceFactory->method('create')->with(RatepayInvoiceTransaction::NAME);

        $response = $this->getMockBuilder(SuccessResponse::class)->disableOriginalConstructor()->getMock();
        $this->notify->method('fromXmlResponse')->with('<xml/>')->willReturn($response);
        $this->updater->fetchNotify($this->transaction);
    }
}
