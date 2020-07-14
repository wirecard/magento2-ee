<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Block\Adminhtml\Support;

use Wirecard\ElasticEngine\Block\Adminhtml\Support\Edit\Form;

/**
 * Test form used for unit tests
 *
 * Class TestForm
 */
class TestForm extends Form
{
    private $requestInterface;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data,
        $requestInterface
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->requestInterface = $requestInterface;
    }

    public function getRequest()
    {
        return $this->requestInterface;
    }

    public function getUrl($route = '', $params = [])
    {
        return "url";
    }

    public function setForm(\Magento\Framework\Data\Form $form)
    {
        return true;
    }

    public function testPrepareForm()
    {
        return $this->_prepareForm();
    }
}
