<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Adminhtml\Support;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;

class Contact extends Container
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_support';
        $this->_blockGroup = 'Wirecard_ElasticEngine';
        $this->buttonList->remove('save');
        $this->buttonList->add(
            'sendrequest',
            [
                'label' => __('send_email'),
                'class' => 'save',
                'onclick' => 'jQuery("#edit_form").submit();',
            ],
            -100,
            0,
            'footer'
        );
    }
}
