<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
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

namespace Wirecard\ElasticEngine\Controller\Adminhtml\Support;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Wirecard\ElasticEngine\Model\Adminhtml\Support;

class Sendrequest extends \Magento\Backend\App\Action
{
    /**
     * @var Support
     */
    protected $_supportModel;

    /** @var PageFactory */
    protected $_resultPageFactory;

    public function __construct(
        Context $context,
        Support $supportModel,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_supportModel = $supportModel;
        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $redirectUrl = $this->getUrl('wirecard_elasticengine/support/contact');

        if (!($data = $this->getRequest()->getPostValue())) {
            $this->_redirect($redirectUrl);
            return;
        }

        $postObject = new \Magento\Framework\DataObject();
        $postObject->setData($data);

        try {
            $this->_supportModel->sendrequest($postObject);
            $this->messageManager->addNoticeMessage(__('Support request sent successfully!'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect($redirectUrl);
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
