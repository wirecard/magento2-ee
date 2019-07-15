<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Adminhtml\Support;

class Index extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $this->_redirect('adminhtml/system_config/edit/section/wirecard_elasticengine');
    }
}
