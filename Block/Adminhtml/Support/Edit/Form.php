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
        return __('Support Request');
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
        $form = $this->_formFactory->create([
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/sendrequest', ['id' => $this->getRequest()->getParam('id')]),
                    'method' => 'post'
                ]
            ]
        );

        $form->setUseContainer(true);

        $fieldset = $form->addFieldset('form_form', ['legend' => __('Contact Form')]);
        $fieldset->addField('to', 'select', [
            self::LABEL => __('To'),
            self::CLASSCONST => 'required-entry',
            'required' => true,
            self::NAME => 'to',
            'options' => [
                'support.at@wirecard.com' => 'Support Team Wirecard CEE, Austria',
                'support@wirecard.com' => 'Support Team Wirecard AG, Germany',
                'support.sg@wirecard.com' => 'Support Team Wirecard Singapore'
            ]
        ]);

        $fieldset->addField('replyto', 'text', [
            self::LABEL => __('Your e-mail address'),
            self::CLASSCONST => 'validate-email',
            self::NAME => 'replyto'
        ]);

        $fieldset->addField('description', 'textarea', [
            self::LABEL => __('Your message'),
            self::CLASSCONST => 'required-entry',
            'required' => true,
            self::NAME => 'description',
            'style' => 'height:30em;width:50em'
        ]);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
