<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Widget;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SupportRequestButton extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);

        $url = $this->getUrl('wirecard_elasticengine/support/contact');
        $html = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Contact support')
            ->setOnClick("setLocation('$url')")
            ->toHtml();

        return $html;
    }
}
