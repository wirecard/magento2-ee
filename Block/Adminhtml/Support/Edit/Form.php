<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Adminhtml\Support\Edit;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Form extends Generic implements TabInterface
{
    const LABEL = 'label';

    const CLASSCONST = 'class';

    const NAME = 'name';

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->getTabTitle();
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('support_email_title');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/sendrequest', ['id' => $this->getRequest()->getParam('id')]),
                    'method' => 'post'
                ]
            ]
        );

        $form->setUseContainer(true);

        $fieldset = $form->addFieldset('form_form', ['legend' => __('support_email_title')]);

        $fieldset->addField('replyto', 'text', [
            self::LABEL => __('config_email'),
            self::CLASSCONST => 'validate-email',
            'required' => true,
            self::NAME => 'replyto'
        ]);

        $fieldset->addField('description', 'textarea', [
            self::LABEL => __('config_message'),
            self::CLASSCONST => 'required-entry',
            'required' => true,
            self::NAME => 'description',
            'style' => 'height:30em;width:50em'
        ]);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
