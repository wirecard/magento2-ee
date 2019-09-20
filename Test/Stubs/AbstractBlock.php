<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Magento\Framework\View\Element;

abstract class AbstractBlock
{
    protected $_layout;

    protected $_eventManager;

    protected $_logger;

    public function __construct(Context $context, array $data = [])
    {
        $this->_layout       = $context->getLayout();
        $this->_logger       = $context->getLogger();
        $this->_eventManager = $context->getEventManager();
    }

    public function getUrl()
    {
    }

    public function getLayout()
    {
        return $this->_layout;
    }

    public function getModuleName()
    {
        return '';
    }

    public function _getData()
    {
        return [];
    }

    public function getNameInLayout()
    {
        return '';
    }
}
