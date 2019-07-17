<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Service;

use Magento\Payment\Gateway\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;

class TransactionServiceFactoryUTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $logger = $this->getMock(LoggerInterface::class);
        $paymentSdkConfigFactory = $this->getMock(ConfigFactoryInterface::class);
        $config = new Config('a', 'b', 'c');

        $paymentSdkConfigFactory->method('create')->willReturn($config);
        /**
         * @var $logger LoggerInterface
         * @var $paymentSdkConfigFactory ConfigFactoryInterface
         */
        $transactionServiceFactory = new TransactionServiceFactory($logger, $paymentSdkConfigFactory);
        $transactionServiceFromFactory = $transactionServiceFactory->create('paypal');

        $transactionService = new TransactionService($config, $logger);

        $this->assertEquals($transactionService, $transactionServiceFromFactory);
    }
}
