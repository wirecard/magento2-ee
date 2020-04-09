<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Layout;

class Sepamandate extends Action
{
    /**
     * @return Layout
     * @since 3.1.2 Update the template to layout
     */
    public function execute()
    {
        /** @var Layout $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        $page->getLayout()->getBlock('frontend.sepamandate');
        $page->setHttpResponseCode('200');

        return $page;
    }
}
