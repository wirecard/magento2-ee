<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Wirecard\ElasticEngine\Block\Adminhtml\System\Config;

/**
 * Class TestCredentials
 * @package Wirecard\ElasticEngine\Block\Adminhtml\System\Config
 * @since 3.0.0
 */
class TestCredentials extends \Magento\Config\Block\System\Config\Form\Field
{
    const TEST_CREDENTIALS_TEMPLATE = 'Wirecard_ElasticEngine::system/config/test_credentials.phtml';

    const TEST_CREDENTIALS_CONTROLLER_PATH = 'wirecard_elasticengine/test/credentials';

    /** @var string */
    private $sectionName;

    /** @var string */
    private $buttonLabel;

    /**
     * @return string
     * @since 3.0.0
     */
    public function getAjaxUrl()
    {
        return $this->getUrl(self::TEST_CREDENTIALS_CONTROLLER_PATH);
    }

    /**
     * @return string
     * @since 3.0.0
     */
    public function getButtonLabel()
    {
        return $this->buttonLabel;
    }

    /**
     * @return string
     * @since 3.0.0
     */
    public function getSectionName()
    {
        return $this->sectionName;
    }

    /**
     * phpcs:ignore NeutronStandard.MagicMethods.RiskyMagicMethod.RiskyMagicMethod
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @since 3.0.0
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $this->init($originalData);
        $this->addData(
            [
                'html_id' => $element->getHtmlId(),
                'section_name' => $this->getSectionName()
            ]
        );

        return $this->_toHtml();
    }

    /**
     * phpcs:ignore NeutronStandard.MagicMethods.RiskyMagicMethod.RiskyMagicMethod
     *
     * @return $this|\Magento\Config\Block\System\Config\Form\Field
     * @since 3.0.0
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(self::TEST_CREDENTIALS_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param array $elementData
     * @since 3.0.0
     */
    private function init($elementData)
    {
        $sectionPath = $elementData['path'];
        $sectionName = str_replace(DIRECTORY_SEPARATOR, '_', $sectionPath);

        $this->sectionName = $sectionName;
        $this->buttonLabel = __($elementData['button_label']);
    }
}
