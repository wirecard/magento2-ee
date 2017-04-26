<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Wirecard\ElasticEngine\Gateway\Service\TransactionServiceFactory;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;

/**
 * Class Notify
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @method \Magento\Framework\App\Request\Http getRequest()
 */
class Notify extends Action
{
    /**
     * @var TransactionServiceFactory
     */
    private $transactionServiceFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Context $context, TransactionServiceFactory $transactionServiceFactory, OrderRepositoryInterface $orderRepository, LoggerInterface $logger)
    {
        $this->transactionServiceFactory = $transactionServiceFactory;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        //get the raw request body
        $payload = $this->getRequest()->getContent();
        $this->logger->debug($payload);
        try {
            $transactionService = $this->transactionServiceFactory->create(PayPalTransaction::NAME);
            //handle response
            $response = $transactionService->handleNotification($payload);
        } catch (MalformedResponseException $e) {
            $this->logger->error('Exception returned: ' . $e->getMessage());
            throw $e;
        }

        $this->logger->info('Notification response is instance of: ' . get_class($response));

        //retrieve order id from response
        $orderId = $response->getCustomFields()->get('orderId');
        if ($response instanceof SuccessResponse) {
            $this->updateOrderState($orderId, Order::STATE_PROCESSING);
        } elseif ($response instanceof FailureResponse) {
            foreach ($response->getStatusCollection() as $status) {
                /**
                 * @var $status Status
                 */
                $this->logger->error(sprintf('Error occured: %s (%s)', $status->getDescription(), $status->getCode()));
            }
            $this->updateOrderState($orderId, Order::STATE_PAYMENT_REVIEW);
        } else {
            $this->logger->warning(sprintf('Unexpected result object for notifications.'));
        }
    }

    /**
     * search for an order by id and update the state/status property
     *
     * @param $orderId
     * @param $newState
     * @return void
     */
    private function updateOrderState($orderId, $newState)
    {
        $order = $this->orderRepository->get($orderId);
        $order->setStatus($newState);
        $order->setState($newState);
        $this->orderRepository->save($order);
    }
}
