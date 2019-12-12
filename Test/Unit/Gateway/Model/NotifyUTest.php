<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentToken;
use Magento\Vault\Model\ResourceModel\PaymentToken as PaymentTokenResourceModel;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Helper\TransactionTypeMapper;
use Wirecard\ElasticEngine\Gateway\Model\Notify;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Entity\StatusCollection;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

class NotifyUTest extends \PHPUnit_Framework_TestCase
{
    const HANDLE_NOTIFICATION = 'handleNotification';
    const GET_CUSTOM_FIELDS = 'getCustomFields';
    const GET_PROVIDER_TRANSACTION_ID = 'getProviderTransactionId';
    const GET_DATA = 'getData';

    /**
     * @var Notify
     */
    private $notify;

    /**
     * @var TransactionService|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionService;

    /**
     * @var CustomFieldCollection|PHPUnit_Framework_MockObject_MockObject
     */
    private $customFields;

    /**
     * @var OrderInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $order;

    /**
     * @var Payment|PHPUnit_Framework_MockObject_MockObject
     */
    private $payment;

    /**
     * @var OrderSearchResultInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderSearchResult;

    /**
     * @var OrderRepositoryInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepository;

    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var InvoiceService|PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction|PHPUnit_Framework_MockObject_MockObject
     */
    private $transaction;

    /**
     * @var array
     */
    private $paymentData;

    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentToken;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var PaymentTokenManagementInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenResourceModel|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenResourceModel;

    /**
     * @var AdapterInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTokenResourceModelDbAdapter;

    /**
     * @var EncryptorInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $encryptor;

    /**
     * @var \Wirecard\ElasticEngine\Gateway\Helper\Order|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderHelper;

    /**
     * @var TransactionTypeMapper
     */
    private $transactionTypeMapper;

    public function setUp()
    {
        /**
         * @var $context Context|PHPUnit_Framework_MockObject_MockObject
         */
        $context           = $this->getMockWithoutInvokingTheOriginalConstructor(Context::class);
        $this->paymentData = [
            'providerTransactionId'          => 1234,
            'providerTransactionReferenceId' => 1234567,
            'requestId'                      => '1-2-3',
            'maskedAccountNumber'            => '5151***5485',
            'authorizationCode'              => '1515',
            'cardholderAuthenticationStatus' => 'Y',
            'creditCardToken'                => '0123456CARDTOKEN'
        ];

        /**
         * @var $httpRequest Http|PHPUnit_Framework_MockObject_MockObject
         */
        $httpRequest = $this->getMockWithoutInvokingTheOriginalConstructor(Http::class);
        $httpRequest->method('getContent')->willReturn('PayLoad');
        $context->method('getRequest')->willReturn($httpRequest);

        /**
         * @var $transactionServiceFactory TransactionServiceFactory|PHPUnit_Framework_MockObject_MockObject
         */
        $transactionServiceFactory = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionServiceFactory::class);
        $this->transactionService = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionService::class);
        $this->transaction = $this->getMockWithoutInvokingTheOriginalConstructor(Transaction::class);
        $transactionServiceFactory->method('create')->willReturn($this->transactionService);

        $orderStatusHistoryInterface = $this->getMockWithoutInvokingTheOriginalConstructor(OrderStatusHistoryInterface::class);
        $orderStatusHistoryInterface->method('setIsCustomerNotified')->willReturn($orderStatusHistoryInterface);

        $this->orderRepository = $this->getMock(OrderRepositoryInterface::class);

        $this->order   = $this->getMockWithoutInvokingTheOriginalConstructor(Order::class);
        $this->payment = $this->getMockWithoutInvokingTheOriginalConstructor(Payment::class);
        $this->order->method('getPayment')->willReturn($this->payment);
        $this->order->method('addStatusHistoryComment')->willReturn($orderStatusHistoryInterface);

        $invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $invoice->method('getOrder')->willReturn($this->order);
        $this->orderRepository->method('get')->willReturn($this->order);
        $this->invoiceService = $this->getMockWithoutInvokingTheOriginalConstructor(InvoiceService::class);
        $this->invoiceService->method('prepareInvoice')->willReturn($invoice);

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->customFields = $this->getMock(CustomFieldCollection::class);
        $this->customFields->method('get')->withConsecutive(['orderId'], ['vaultEnabler'])->willReturnOnConsecutiveCalls(42, "true");

        $this->orderSearchResult = $this->getMockForAbstractClass(OrderSearchResultInterface::class);

        $searchCriteria = $this->getMockWithoutInvokingTheOriginalConstructor(SearchCriteria::class);

        $searchCriteriaBuilder = $this->getMockWithoutInvokingTheOriginalConstructor(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->method('addFilter')->willReturn($searchCriteriaBuilder);
        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $transaction = $this->getMockBuilder(Transaction::class)->disableOriginalConstructor()->getMock();
        $transaction->method('addObject')->withAnyParameters()->willReturn($transaction);

        $this->paymentToken = $this->getMockWithoutInvokingTheOriginalConstructor(PaymentToken::class);
        $this->paymentToken->method('getCustomerId')->willReturn(1);

        $extensionAttributesMock = $this->getMock(OrderPaymentExtensionInterface::class, ['setVaultPaymentToken']);
        $extensionAttributesMock->method('setVaultPaymentToken')->willReturn($this->paymentToken);
        $this->paymentExtensionFactory = $this->getMockBuilder(OrderPaymentExtensionInterfaceFactory::class)
            ->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $this->paymentExtensionFactory->method('create')->willReturn($extensionAttributesMock);

        $this->paymentTokenFactory = $this->getMockBuilder(PaymentTokenInterfaceFactory::class)->disableOriginalConstructor()->setMethods(['create'])->getMockForAbstractClass();
        $this->paymentTokenFactory->method('create')->willReturn($this->paymentToken);

        $this->paymentTokenManagement = $this->getMockWithoutInvokingTheOriginalConstructor(PaymentTokenManagementInterface::class);

        $this->paymentTokenResourceModel          = $this->getMockWithoutInvokingTheOriginalConstructor(PaymentTokenResourceModel::class);
        $this->paymentTokenResourceModelDbAdapter = $this->getMockWithoutInvokingTheOriginalConstructor(AdapterInterface::class);
        $this->paymentTokenResourceModel->method('getConnection')->willReturn($this->paymentTokenResourceModelDbAdapter);

        $this->encryptor = $this->getMockWithoutInvokingTheOriginalConstructor(EncryptorInterface::class);

        $this->orderHelper = $this->getMockWithoutInvokingTheOriginalConstructor(\Wirecard\ElasticEngine\Gateway\Helper\Order::class);
        $this->orderHelper->method('getOrderByIncrementId')->willReturn($this->order);

        $this->transactionTypeMapper = $this->getMockWithoutInvokingTheOriginalConstructor(TransactionTypeMapper::class);
        $this->transactionTypeMapper->method('getMappedTransactionType')->willReturn(null);

        $this->notify = new \Wirecard\ElasticEngine\Test\Unit\Gateway\Model\MyNotify(
            $transactionServiceFactory,
            $this->orderRepository,
            $this->logger,
            $this->orderHelper,
            $this->invoiceService,
            $transaction,
            $this->paymentExtensionFactory,
            $this->paymentTokenFactory,
            $this->paymentTokenManagement,
            $this->paymentTokenResourceModel,
            $this->encryptor,
            $this->transactionTypeMapper
        );
    }

    public function testExecuteWithSuccessResponse()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);

        $this->order->expects($this->once())->method('setStatus')->with('processing');
        $this->order->expects($this->once())->method('setState')->with('processing');
        $this->notify->process($successResponse);
    }

    public function testExecuteWithSuccessResponseAndCanCapture()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);
        $successResponse->method('getTransactionType')->willReturn('debit');

        $this->order->expects($this->once())->method('setStatus')->with('processing');
        $this->order->expects($this->once())->method('setState')->with('processing');
        $this->notify->process($successResponse);
    }

    //$this->transactionService->expects($this->once())->method(self::HANDLE_NOTIFICATION)->willReturn($successResponse);
    public function testExecuteWithSuccessResponseAndCheckPayerResponse()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);
        $successResponse->method('getTransactionType')->willReturn('check-payer-response');

        $this->order->expects($this->once())->method('setStatus')->with('processing');
        $this->order->expects($this->once())->method('setState')->with('processing');
        $this->notify->process($successResponse);
    }

    public function testExecuteWithSuccessResponseAndAuthorization()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);
        $successResponse->method('getTransactionType')->willReturn('authorization');

        $this->order->expects($this->once())->method('setStatus')->with('processing');
        $this->order->expects($this->once())->method('setState')->with('processing');
        $this->notify->process($successResponse);
    }

    private function setDefaultOrder()
    {
        $this->orderSearchResult->method('getItems')->willReturn([$this->order]);
        $this->orderRepository->method('getList')->willReturn($this->orderSearchResult);
    }

    /*public function testExecuteWithFraudResponse()
    {
        $this->setDefaultOrder();

        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_PROVIDER_TRANSACTION_ID)->willReturn(1234);
        $successResponse->method('isValidSignature')->willReturn(false);
        $this->transactionService->expects($this->once())->method(self::HANDLE_NOTIFICATION)->willReturn($successResponse);

        $this->order->expects($this->once())->method('setStatus')->with('fraud');
        $this->order->expects($this->once())->method('setState')->with('fraud');
        $this->notify->handle('');
    }*/

    public function testExecuteWithInvalidOrderNumber()
    {
        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);

        $this->orderHelper->method('getOrderByIncrementId')->willThrowException(new NoSuchEntityException());

        $this->logger->expects($this->once())->method('warning')->with('Order with orderID 42 not found.');
        $this->notify->process($successResponse);
    }

    public function testExecuteWithFailureResponse()
    {
        $this->setDefaultOrder();

        /** @var FailureResponse|PHPUnit_Framework_MockObject_MockObject $failureResponse */
        $failureResponse = $this->getMockWithoutInvokingTheOriginalConstructor(FailureResponse::class);
        $failureResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $statusCollection = $this->getMock(StatusCollection::class);
        $status           = $this->getMockWithoutInvokingTheOriginalConstructor(Status::class);
        $iterator         = new \ArrayIterator([$status, $status]);
        $statusCollection->method('getIterator')->willReturn($iterator);
        $failureResponse->expects($this->once())->method('getStatusCollection')->willReturn($statusCollection);

        $this->order->expects($this->once())->method('cancel');
        $this->notify->process($failureResponse);
    }

    /**
     * @expectedException \Wirecard\PaymentSdk\Exception\MalformedResponseException
     */
    public function testExecuteWithMalformedPayload()
    {
        $this->transactionService
            ->expects($this->once())
            ->method(self::HANDLE_NOTIFICATION)
            ->willThrowException(new MalformedResponseException('Message'));

        $this->logger->expects($this->once())->method('error')->with('Response is malformed: Message');

        $this->notify->fromXmlResponse('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteWithInvalidArgument()
    {
        $this->transactionService
            ->expects($this->once())
            ->method(self::HANDLE_NOTIFICATION)
            ->willThrowException(new \InvalidArgumentException('Message'));

        $this->logger->expects($this->once())->method('error')->with('Invalid argument set: Message');

        $this->notify->fromXmlResponse('');
    }

    public function testExecuteWithUnexpecterResponseObject()
    {
        $this->setDefaultOrder();

        //this Response will never happen on notificy call
        /** @var Response|PHPUnit_Framework_MockObject_MockObject $unexpectedResponse */
        $unexpectedResponse = $this->getMockWithoutInvokingTheOriginalConstructor(InteractionResponse::class);
        $unexpectedResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);

        $this->notify->process($unexpectedResponse);
    }

    public function testExecuteWillUpdatePayment()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('getParentTransactionId')->willReturn(999);

        $this->payment->expects($this->once())->method('setParentTransactionId')->with(999);
        $this->payment->expects($this->once())->method('setTransactionAdditionalInfo')->with(
            'raw_details_info',
            [
                'providerTransactionId'          => 1234,
                'providerTransactionReferenceId' => 1234567,
                'requestId'                      => '1-2-3',
                'maskedAccountNumber'            => '5151***5485',
                'authorizationCode'              => '1515',
                'cardholderAuthenticationStatus' => 'Y',
                'creditCardToken'                => '0123456CARDTOKEN'
            ]
        );

        $this->notify->process($successResponse);
    }

    public function testHandleSuccess()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);

        $this->payment->method('getAdditionalInformation')
            ->with(CreditCardDataAssignObserver::VAULT_ENABLER)->willReturn(true);

        $this->paymentTokenManagement
            ->method('saveTokenWithPaymentLink')
            ->willThrowException(new AlreadyExistsException(new Phrase('Unique constraint violation found')));

        $this->paymentTokenResourceModelDbAdapter->expects($this->once())->method('delete');

        $this->notify->myHandleSuccess($this->order, $successResponse);
    }

    public function testMigrateToken()
    {
        $this->setDefaultOrder();

        /** @var SuccessResponse|PHPUnit_Framework_MockObject_MockObject $successResponse */
        $successResponse = $this->getMockWithoutInvokingTheOriginalConstructor(SuccessResponse::class);
        $successResponse->method(self::GET_CUSTOM_FIELDS)->willReturn($this->customFields);
        $successResponse->method(self::GET_DATA)->willReturn($this->paymentData);
        $successResponse->method('isValidSignature')->willReturn(true);

        $this->payment->method('getAdditionalInformation')
            ->with(CreditCardDataAssignObserver::VAULT_ENABLER)->willReturn(true);

        $this->paymentTokenManagement
            ->method('getByPublicHash')
            ->willReturn($this->paymentToken);

        $this->paymentTokenResourceModel->expects($this->once())->method('delete');
        $this->paymentTokenResourceModelDbAdapter->expects($this->once())->method('delete');

        $this->notify->myHandleSuccess($this->order, $successResponse);
    }

    public function testGeneratePublicHash()
    {
        $this->paymentToken->method('getGatewayToken')->willReturn('4304509873471003');
        $this->paymentToken->method('getPaymentMethodCode')->willReturn('wirecard_elasticengine_creditcard');
        $this->paymentToken->method('getType')->willReturn('card');
        $this->paymentToken->method('getTokenDetails')
            ->willReturn('{"type":"","maskedCC":"1003","expirationDate":"xx-xxxx"}');

        $this->encryptor->method('getHash')->will($this->returnCallback(function ($arg) {
            return md5($arg);
        }));

        $hash = $this->notify->myGeneratePublicHash($this->paymentToken);
        $this->assertEquals('98cb19a0753e9ae138466da73c4ead19', $hash);
    }
}

class MyNotify extends Notify
{
    public function myHandleSuccess($order, $response)
    {
        $this->handleSuccess($order, $response);
    }

    public function myGeneratePublicHash($paymentToken)
    {
        return $this->generatePublicHash($paymentToken);
    }
}
