<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Request\TransactionFactory;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Zend\Loader\Exception\InvalidArgumentException;

/**
 * Class WirecardCommand
 */
class WirecardCaptureCommand implements CommandInterface
{
    const STATEOBJECT='stateObject';

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var HandlerInterface
     */
    private $handler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigInterface
     */
    private $methodConfig;

    /**
     * WirecardCommand constructor.
     * @param TransactionFactory $transactionFactory
     * @param TransactionServiceFactory $transactionServiceFactory
     * @param LoggerInterface $logger
     * @param HandlerInterface $handler
     * @param ConfigInterface $methodConfig
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionServiceFactory $transactionServiceFactory,
        LoggerInterface $logger,
        HandlerInterface $handler,
        ConfigInterface $methodConfig
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->logger = $logger;
        $this->handler = $handler;
        $this->methodConfig = $methodConfig;
    }

    /**
     * @param array $commandSubject
     * @return void
     */
    public function execute(array $commandSubject)
    {
        $transaction = $this->transactionFactory->capture($commandSubject);
        $transactionService = $this->transactionServiceFactory->create($transaction::NAME);

        try {
            if ($transaction instanceof CreditCardTransaction
                && $this->methodConfig->getValue('three_d_merchant_account_id') !== ''
            ) {
                $transaction->setThreeD(false);
            }
            $response = $transactionService->process($transaction, Operation::PAY);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $response = null;
        }

        if ($this->handler) {
            $this->handler->handle($commandSubject, ['paymentSDK-php' => $response]);
        }

        if ($response instanceof FailureResponse) {
            $errors = "";
            foreach ($response->getStatusCollection()->getIterator() as $item) {
                /** @var Status $item */
                $errors .= $item->getDescription() . "<br>\n";
            }
            throw new InvalidArgumentException($errors);
        }
    }
}
