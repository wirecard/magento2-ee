<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Adminhtml\Test;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;

class Credentials extends Action
{

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Credentials constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, LoggerInterface $logger)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $this->logger->debug('this is baseUrl: ' . $data['baseUrl']);

        $config = new Config($data['baseUrl'], $data['httpUser'], $data['httpPass']);
        $transactionService = new TransactionService($config, $this->logger);

        $message = __('error_credentials');
        if ($valid = $transactionService->checkCredentials()) {
            $message = __('success_credentials');
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData(['valid' => $valid, 'message' => $message]);
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Payment::payment');
    }
}
