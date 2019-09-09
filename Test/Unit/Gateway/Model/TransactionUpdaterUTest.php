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
use Wirecard\ElasticEngine\Gateway\Model\Notify;
use Wirecard\ElasticEngine\Gateway\Model\RetreiveTransaction;
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
     * @var RetreiveTransaction|PHPUnit_Framework_MockObject_MockObject
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

    public function setUp()
    {
        $this->transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $transactionService              = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        $this->config                    = $this->getMockWithoutInvokingTheOriginalConstructor(Config::class);
        $transactionService->method('getConfig')->willReturn($this->config);
        $this->transactionServiceFactory->method('create')->willReturn($transactionService);

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->transactionCollection = $this->getMockWithoutInvokingTheOriginalConstructor(Collection::class);

        $this->transactionRepository = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionRepository::class);
        $this->transaction           = $this->getMockWithoutInvokingTheOriginalConstructor(Transaction::class);
        $this->transactionRepository->method('get')->willReturn($this->transaction);

        $this->retreiveTransaction = $this->getMockWithoutInvokingTheOriginalConstructor(RetreiveTransaction::class);

        $this->notify = $this->getMockWithoutInvokingTheOriginalConstructor(Notify::class);

        $this->updater = new TransactionUpdater(
            $this->logger,
            $this->transactionServiceFactory,
            $this->transactionCollection,
            $this->transactionRepository,
            $this->retreiveTransaction,
            $this->notify
        );
    }

    public function testRun()
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

        $this->retreiveTransaction->method('byRequestId')->with($this->config, 'rid', 'maid');
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyWithoutMethodName()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "request-id"          => "rid",
                "transaction-type"    => "ttype",
                "merchant-account-id" => "maid",
                "transaction-id"      => "tid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutRequestId()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "transaction-type"       => "ttype",
                "transaction-id"         => "tid",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutTransactionType()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "request-id"             => "rid",
                "transaction-id"         => "tid",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutTransactionId()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "request-id"             => "rid",
                "transaction-type"       => "ttype",
                "merchant-account-id"    => "maid"

            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->expects($this->never())->method('byRequestId');
        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyWithoutMaid()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "creditcard",
                "request-id"             => "rid",
                "transaction-id"         => "tid",
                "transaction-type"       => "ttype",
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

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

        $this->retreiveTransaction->method('byRequestId')->willReturn(false);

        $this->retreiveTransaction->method('byTransactionId')
            ->with($this->config, "tid", "ttype", "maid")
            ->willReturn('<xml/>');
        $this->notify->method('fromXmlResponse')->with('<xml/>');
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyNothingFound()
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

        $this->retreiveTransaction->method('byRequestId')->willReturn(false);
        $this->retreiveTransaction->method('byTransactionId')->willReturn(false);

        $ret = $this->updater->fetchNotify($this->transaction);
        $this->assertNull($ret);
    }

    public function testFetchNotifyAlipay()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => AlipayCrossborderTransaction::NAME,
                "request-id"             => "rid-get-url", // alipay has -get-url appended
                "transaction-id"         => "tid",
                "transaction-type"       => "ttype",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->method('byRequestId')
            ->with($this->config, "rid", "maid")
            ->willReturn('<xml/>');

        $this->notify->method('fromXmlResponse')->with('<xml/>');
        $this->updater->fetchNotify($this->transaction);
    }

    public function testFetchNotifyRatepayInvoice()
    {
        $rawData = (object)[
            "raw_details_info" => [
                "payment-methods.0.name" => "ratepay-invoice", // must be converted to ratepayinvoice
                "request-id"             => "rid",
                "transaction-id"         => "tid",
                "transaction-type"       => "ttype",
                "merchant-account-id"    => "maid"
            ]
        ];
        $this->transaction->method('getData')->willReturn(json_encode($rawData));

        $this->retreiveTransaction->method('byRequestId')
            ->with($this->config, "rid", "maid")
            ->willReturn('<xml/>');

        $this->transactionServiceFactory->method('create')->with(RatepayInvoiceTransaction::NAME);

        $this->notify->method('fromXmlResponse')->with('<xml/>');
        $this->updater->fetchNotify($this->transaction);
    }
}
