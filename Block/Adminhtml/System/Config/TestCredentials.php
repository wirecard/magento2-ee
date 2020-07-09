<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Block\Adminhtml\System\Config;

/**
 * Class for getting data for testing payment methods in the Magento 2 backend
 *
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
