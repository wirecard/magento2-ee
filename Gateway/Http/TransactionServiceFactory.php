<?php
/**
 * Created by IntelliJ IDEA.
 * User: timon.roenisch
 * Date: 20.04.2017
 * Time: 13:42
 */

namespace Wirecard\ElasticEngine\Gateway\Http;

use Magento\Payment\Gateway\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\TransactionService;

class TransactionServiceFactory
{
    /**
     * @var ConfigFactoryInterface
     */
    private $paymentSdkConfigFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * TransactionServiceFactory constructor.
     * @param LoggerInterface $logger
     * @param ConfigFactoryInterface $paymentSdkConfigFactory
     */
    public function __construct(LoggerInterface $logger, ConfigFactoryInterface $paymentSdkConfigFactory)
    {
        $this->logger = $logger;
        $this->paymentSdkConfigFactory = $paymentSdkConfigFactory;
    }

    /**
     * @param $methodCode
     * @return TransactionService
     */
    public function create($methodCode) {
        $txConfig = $this->paymentSdkConfigFactory->create($methodCode);
        return new TransactionService($txConfig, $this->logger);
    }
}