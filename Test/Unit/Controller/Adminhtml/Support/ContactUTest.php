<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Adminhtml\Support;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Wirecard\ElasticEngine\Controller\Adminhtml\Support\Contact;
use WirecardTest\ElasticEngine\Unit\Controller\Adminhtml\Support\TestContact;

/**
 * Class CredentialsTest
 */
class ContactUTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Contact $contact
     */
    private $contact;

    /**
     * @var PageFactory $resultPageFactory
     */
    private $resultPageFactory;

    private $page;

    /**
     * @var Context $context
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $title = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultPageConfig = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultPageConfig->method('getTitle')
            ->willReturn($title);

        $this->page = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->page->method('setActiveMenu')
            ->willReturn($this->page);
        $this->page->method('getConfig')
            ->willReturn($resultPageConfig);

        $this->resultPageFactory = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultPageFactory->method('create')
            ->willReturn($this->page);

        $this->contact = new Contact($this->context, $this->resultPageFactory);
    }

    public function testExecute()
    {
        $this->assertEquals($this->page, $this->contact->execute());
    }

    public function testIsAllowed()
    {
        $authorization = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $testContact = new TestContact($this->context, $this->resultPageFactory);
        $this->assertNull($testContact->testIsAllowed($authorization));
    }
}
