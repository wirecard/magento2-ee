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

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Command;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Command\WirecardCommand;
use Wirecard\ElasticEngine\Gateway\Request\TransactionFactory;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\ElasticEngine\Model\Adminhtml\Source\PaymentAction;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Entity\StatusCollection;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;

class WirecardCommandUTest extends \PHPUnit_Framework_TestCase
{
    const METHOD_CREATE='create';
    const METHOD_PROCESS='process';
    const RECURRING = true;
    const NOT_RECURRING = false;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ConfigInterface
     */
    private $methodConfig;

    /**
     * @var PaymentDataObject
     */
    private $paymentDO;

    /**
     * @var array
     */
    private $commandSubject;

    public function setUp()
    {
        // Transaction mocks
        $this->response = $this->getMockBuilder(SuccessResponse::class)
            ->disableOriginalConstructor()->getMock();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->transactionFactory->method(self::METHOD_CREATE)
            ->willReturn($this->getMock(PayPalTransaction::class));

        // TransactionService mocks
        $this->transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();
        $this->transactionService->method(self::METHOD_PROCESS)->willReturn($this->response);

        $this->transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $this->transactionServiceFactory->method(self::METHOD_CREATE)->willReturn($this->transactionService);

        $this->logger = $this->getMock(LoggerInterface::class);

        $this->handler = $this->getMock(HandlerInterface::class);

        $this->methodConfig = $this->getMock(ConfigInterface::class);
        $this->methodConfig->method('getValue')->willReturn('authorize');

        $stateObject = $this->getMock(DataObject::class);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getAdditionalInformation')->willReturn(true);

        $this->paymentDO = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getPayment'])->getMock();
        $this->paymentDO->method('getPayment')->willReturn($payment);

        $this->commandSubject = [
            'stateObject' => $stateObject,
            'payment' => $this->paymentDO
        ];
    }

    public function testExecuteCreatesTransactionService()
    {
        $testTransactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $testTransactionServiceFactory->method(self::METHOD_CREATE)->willReturn($this->transactionService);

        // Test if the transactionService is created with the correct values
        $testTransactionServiceFactory->expects($this->Once())->method(self::METHOD_CREATE);

        /** @var TransactionServiceFactory $testTransactionServiceFactory */
        $command = new WirecardCommand(
            $this->transactionFactory,
            $testTransactionServiceFactory,
            $this->logger,
            $this->handler,
            $this->methodConfig
        );

        $command->execute($this->commandSubject);
    }

    public function transactionDataProvider()
    {
        return [
            [ Transaction::class, PaymentAction::AUTHORIZE, Operation::PAY, self::RECURRING],
            [ CreditCardTransaction::class, PaymentAction::AUTHORIZE, Operation::RESERVE, self::RECURRING],
            [ Transaction::class, PaymentAction::AUTHORIZE_CAPTURE, Operation::PAY, self::RECURRING],
            [ PayPalTransaction::class, PaymentAction::AUTHORIZE, Operation::RESERVE, self::NOT_RECURRING],
            [ PayPalTransaction::class, PaymentAction::AUTHORIZE_CAPTURE, Operation::PAY, self::RECURRING]
        ];
    }

    /**
     * @dataProvider transactionDataProvider
     * @param $transactionClass
     * @param $configuredAction
     * @param $expectedOperation
     */
    public function testExecuteUsesCorrectOperation($transactionClass, $configuredAction, $expectedOperation, $recurringPayment)
    {
        // Transaction mocks
        $transactionFactoryMock = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionFactoryMock->method(self::METHOD_CREATE)->willReturn($this->getMock($transactionClass));

        // TransactionService mocks
        /** @var \PHPUnit_Framework_MockObject_MockObject $transactionServiceMock */
        $transactionServiceMock = $this->transactionService;

        // Test if transactionService->process(...) is called with the correct parameters
        $transactionServiceMock->expects($this->Once())->method(self::METHOD_PROCESS)->with(
            $this->equalTo($this->getMock($transactionClass)), $this->equalTo($expectedOperation)
        );

        $transactionServiceFactoryMock = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactoryMock->method(self::METHOD_CREATE)->willReturn($transactionServiceMock);

        // Payment method config mocks
        $methodConfigMock = $this->getMock(ConfigInterface::class);
        $methodConfigMock->method('getValue')->willReturn($configuredAction);

        $payment = $this->getMockBuilder(Payment::class)->disableOriginalConstructor()->getMock();
        $payment->method('getAdditionalInformation')->willReturn($recurringPayment);

        $this->paymentDO = $this->getMockBuilder(DataObject::class)->disableOriginalConstructor()->setMethods(['getPayment'])->getMock();
        $this->paymentDO->method('getPayment')->willReturn($payment);

        $this->commandSubject['payment'] = $this->paymentDO;

        /**
         * @var TransactionFactory $transactionFactoryMock
         * @var TransactionServiceFactory $transactionServiceFactoryMock
         */
        $command = new WirecardCommand(
            $transactionFactoryMock,
            $transactionServiceFactoryMock,
            $this->logger,
            $this->handler,
            $methodConfigMock
        );

        $command->execute($this->commandSubject);
    }

    /**
     * @dataProvider transactionDataProvider
     * @param $transactionClass
     */
    public function testExecuteLogsException($transactionClass)
    {
        // Transaction mocks
        $transactionFactoryMock = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionFactoryMock->method(self::METHOD_CREATE)->willReturn($this->getMock($transactionClass));

        $exception = new \Exception('Testing the exception');

        // TransactionService mocks
        $transactionServiceMock = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceMock->method(self::METHOD_PROCESS)
            ->willThrowException($exception);

        $transactionServiceFactoryMock = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactoryMock->method(self::METHOD_CREATE)->willReturn($transactionServiceMock);

        // Test if the logger gets the exception message
        $loggerMock = $this->getMock(LoggerInterface::class);
        $loggerMock->expects($this->Once())->method('error')->with($this->equalTo($exception->getMessage()));

        /**
         * @var TransactionFactory $transactionFactoryMock
         * @var TransactionServiceFactory $transactionServiceFactoryMock
         */
        $command = new WirecardCommand(
            $transactionFactoryMock,
            $transactionServiceFactoryMock,
            $loggerMock,
            $this->handler,
            $this->methodConfig
        );

        $command->execute($this->commandSubject);
    }

    public function testExecuteCallsHandler()
    {
        $handlerMock = $this->getMock(HandlerInterface::class);

        $handlerMock->expects($this->Once())->method('handle')
            ->with($this->equalTo($this->commandSubject), $this->equalTo(['paymentSDK-php' => $this->response]));

        $command = new WirecardCommand(
            $this->transactionFactory,
            $this->transactionServiceFactory,
            $this->logger,
            $handlerMock,
            $this->methodConfig
        );
        $command->execute($this->commandSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteThrows()
    {
        // TransactionService mocks
        $transactionServiceMock = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()->getMock();

        $status = $this->getMockBuilder(Status::class)->disableOriginalConstructor()->getMock();
        $status->method('getDescription')->willReturn('description');

        $statusCollection = $this->getMockBuilder(StatusCollection::class)->disableArgumentCloning()->getMock();
        $statusCollection->method('getIterator')->willReturn([$status]);

        $failureResponse = $this->getMockBuilder(FailureResponse::class)->disableOriginalConstructor()->getMock();
        $failureResponse->method('getStatusCollection')->willReturn($statusCollection);

        $transactionServiceMock->method('process')->willReturn($failureResponse);

        $transactionServiceFactoryMock = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()->getMock();
        $transactionServiceFactoryMock->method('create')->willReturn($transactionServiceMock);

        $command = new WirecardCommand(
            $this->transactionFactory,
            $transactionServiceFactoryMock,
            $this->logger,
            $this->handler,
            $this->methodConfig
        );

        $stateObject = $this->getMock(DataObject::class);

        $commandSubject = [
            'stateObject' => $stateObject,
            'payment' =>    $this->paymentDO
        ];

        $command->execute($commandSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteThrowsException()
    {
        $command = new WirecardCommand(
            $this->transactionFactory,
            $this->transactionServiceFactory,
            $this->logger,
            $this->handler,
            $this->methodConfig
        );
        $command->execute([]);
    }
}
