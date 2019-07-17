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

/**
 * Class CredentialsTest
 * @package Wirecard\ElasticEngine\Test\Unit\Adminhtml\Test
 * @method _isAllowed()
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

    private $context;

    public function setUp()
    {
        $this->context = $this->getMock(Context::class, [], [], '', false);

        $title = $this->getMock(Title::class, [], [], '', false);

        $resultPageConfig = $this->getMock(Config::class, [], [], '', false);
        $resultPageConfig->method('getTitle')->willReturn($title);

        $this->page = $this->getMock(Page::class, [], [], '', false);
        $this->page->method('setActiveMenu')->willReturn($this->page);
        $this->page->method('getConfig')->willReturn($resultPageConfig);

        $this->resultPageFactory = $this->getMock(PageFactory::class, ['create'], [], '', false);
        $this->resultPageFactory->method('create')->willReturn($this->page);

        $this->contact = new Contact($this->context, $this->resultPageFactory);
    }

    public function testExecute()
    {
        $this->assertEquals($this->page, $this->contact->execute());
    }

    public function testIsAllowed()
    {
        $authorization = $this->getMock(AuthorizationInterface::class, [], [], '', false);

        $testContact = new TestContact($this->context, $this->resultPageFactory);
        $this->assertNull($testContact->test_isAllowed($authorization));
    }
}

class TestContact extends Contact
{
    public function test_isAllowed($authorization)
    {
        $this->_authorization = $authorization;
        return $this->_isAllowed();
    }
}
