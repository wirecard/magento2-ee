<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactory = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactory->method(self::METHOD_CREATE)
            ->willReturn($this->getMockBuilder(PayPalTransaction::class)->getMock());

        // TransactionService mocks
        $this->transactionService = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionService->method(self::METHOD_PROCESS)->willReturn($this->response);

        $this->transactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionServiceFactory->method(self::METHOD_CREATE)
            ->willReturn($this->transactionService);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->handler = $this->getMockBuilder(HandlerInterface::class)
            ->getMock();

        $this->methodConfig = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
        $this->methodConfig->method('getValue')
            ->willReturn('authorize');

        $stateObject = $this->getMockBuilder(DataObject::class)
            ->getMock();

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment->method('getAdditionalInformation')
            ->willReturn(true);

        $this->paymentDO = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment'])
            ->getMock();
        $this->paymentDO->method('getPayment')
            ->willReturn($payment);

        $this->commandSubject = [
            'stateObject' => $stateObject,
            'payment' => $this->paymentDO
        ];
    }

    public function testExecuteCreatesTransactionService()
    {
        $testTransactionServiceFactory = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $testTransactionServiceFactory->method(self::METHOD_CREATE)
            ->willReturn($this->transactionService);

        // Test if the transactionService is created with the correct values
        $testTransactionServiceFactory->expects($this->Once())
            ->method(self::METHOD_CREATE);

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

    public function testExecuteCallsHandler()
    {
        $handlerMock = $this->getMockBuilder(HandlerInterface::class)->getMock();

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

        $status = $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->getMock();
        $status->method('getDescription')
            ->willReturn('description');

        $statusCollection = $this->getMockBuilder(StatusCollection::class)
            ->disableArgumentCloning()
            ->getMock();
        $statusCollection->method('getIterator')
            ->willReturn([$status]);

        $failureResponse = $this->getMockBuilder(FailureResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $failureResponse->method('getStatusCollection')
            ->willReturn($statusCollection);

        $transactionServiceMock->method('process')
            ->willReturn($failureResponse);

        $transactionServiceFactoryMock = $this->getMockBuilder(TransactionServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transactionServiceFactoryMock->method('create')
            ->willReturn($transactionServiceMock);

        $command = new WirecardCommand(
            $this->transactionFactory,
            $transactionServiceFactoryMock,
            $this->logger,
            $this->handler,
            $this->methodConfig
        );

        $stateObject = $this->getMockBuilder(DataObject::class)
            ->getMock();

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
