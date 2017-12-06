<?php

namespace Wirecard\ElasticEngine\Test\Unit\Block\Adminhtml\Support;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form as MagentoForm;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Wirecard\ElasticEngine\Block\Adminhtml\Support\Edit\Form;

class FormUTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Form $form
     */
    private $form;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject $context
     */
    private $context;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject $registry
     */
    private $registry;

    /**
     * @var FormFactory|\PHPUnit_Framework_MockObject_MockObject $formFactory
     */
    private $formFactory;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock();

        $fieldset = $this->getMock(Fieldset::class, [], [], '', false);

        $form = $this->getMock(MagentoForm::class, [], [], '', false);
        $form->method('addFieldset')->willReturn($fieldset);

        $this->formFactory = $this->getMockBuilder(FormFactory::class)->disableOriginalConstructor()->getMock();
        $this->formFactory->method('create')->willReturn($form);

        $this->form = new Form($this->context, $this->registry, $this->formFactory);
    }

    public function testGetTabLabel()
    {
        $this->assertEquals(__('Support Request'), $this->form->getTabLabel());
    }

    public function testCanShowTab()
    {
        $this->assertTrue($this->form->canShowTab());
    }

    public function testIsHidden()
    {
        $this->assertFalse($this->form->isHidden());
    }

    public function testPrepareForm()
    {
        $requestInterface = $this->getMock(RequestInterface::class, [], [], '', false);

        $testForm = new TestForm($this->context, $this->registry, $this->formFactory, [], $requestInterface);

        $testForm->test_prepareForm();
    }
}

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

    public function test_prepareForm()
    {
        return $this->_prepareForm();
    }
}
