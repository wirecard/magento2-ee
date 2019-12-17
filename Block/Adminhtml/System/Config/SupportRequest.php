<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Wirecard\ElasticEngine\Block\Adminhtml\System\Config;

class SupportRequest extends \Magento\Config\Block\System\Config\Form\Field
{
    const SUPPORT_REQUEST_TEMPLATE = 'Wirecard_ElasticEngine::system/config/send_supportrequest.phtml';

    private $sectionPath;

    private $buttonLabel;

    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return $this->buttonLabel;
    }

    /**
     * @return string
     */
    public function getSectionPath()
    {
        return $this->sectionPath;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $path = $originalData['path'];
        $this->initSectionPath($path);
        $this->buttonLabel = __($originalData['button_label']);
        $this->addData(
            [
                'html_id' => $element->getHtmlId(),
                'section_path' => $this->getSectionPath()
            ]
        );

        return $this->_toHtml();
    }

    /**
     * @return $this|\Magento\Config\Block\System\Config\Form\Field
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(self::SUPPORT_REQUEST_TEMPLATE);
        }
        return $this;
    }

    /**
     * @param $path
     */
    private function initSectionPath($path)
    {
        $sectionPath = str_replace(DIRECTORY_SEPARATOR, '_', $path);
        $this->sectionPath = $sectionPath;
    }
}