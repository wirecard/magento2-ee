<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
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
            $this->messageManager->addNoticeMessage(__('success_email'));
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
