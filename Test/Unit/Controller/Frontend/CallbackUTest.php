<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
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

namespace Wirecard\ElasticEngine\Test\Unit\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\UrlInterface;
use Wirecard\ElasticEngine\Controller\Frontend\Callback;

class CallbackUTest extends \PHPUnit_Framework_TestCase
{
    const HAS_REDIRECT_URL = 'hasRedirectUrl';
    const HAS_FORM_URL = 'hasFormUrl';
    const RESPONSE_JSON = 'representJson';

    private $resultFactory;

    private $context;

    /**
     * @var Json
     */
    private $json;

    private $session;

    private $response;

    public function setUp()
    {
        $inline = $this->getMockForAbstractClass(InlineInterface::class);
        $inline->method('processResponseBody')->willReturn(null);

        $this->json = new Json($inline);

        $this->resultFactory = $this->getMOckBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultFactory->method('create')->willReturn($this->json);

        $urlBuilder = $this->getMock(UrlInterface::class);
        $urlBuilder->method('getRouteUrl')->willReturn('http://magen.to/');
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResultFactory', 'getUrl'])
            ->getMock();
        $this->context->method('getUrl')->willReturn($urlBuilder);

        $this->context->method('getResultFactory')->willReturn($this->resultFactory);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getRedirectUrl',
                self::HAS_REDIRECT_URL,
                'unsRedirectUrl',
                'getFormUrl',
                self::HAS_FORM_URL,
                'unsFormUrl',
                'getFormMethod',
                'unsFormMethod',
                'getFormFields',
                'unsFormFields',
            ])
            ->getMock();
        $this->session->method('getRedirectUrl')->willReturn('http://redir.ect');
        $this->session->method('getFormUrl')->willReturn('http://formpost.ect');
        $this->session->method('getFormMethod')->willReturn('post');
        $this->session->method('getFormFields')->willReturn('myfieldsarray');

        $this->response = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods([self::RESPONSE_JSON])
            ->getMock();
    }

    public function testGetRedirectUnsetsRedirect()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)->willReturn(true);
        $sessionMock->expects($this->once())->method('unsRedirectUrl');

        /** @var $sessionMock Session */
        $redirect = new Callback($this->context, $sessionMock);
        $redirect->execute();
    }

    public function testGetFormUnsetsForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_FORM_URL)->willReturn(true);
        $sessionMock->expects($this->once())->method('unsFormUrl');
        $sessionMock->expects($this->once())->method('unsFormMethod');
        $sessionMock->expects($this->once())->method('unsFormFields');

        /** @var $sessionMock Session */
        $redirect = new Callback($this->context, $sessionMock);
        $redirect->execute();
    }

    public function testGetFormWhenSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_FORM_URL)->willReturn(true);

        /** @var Session $sessionMock */
        $redirect = new Callback($this->context, $sessionMock);
        $result = $redirect->execute();

        /** @var \PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with('{"redirect-url":null,"form-url":"http:\/\/formpost.ect","form-method":"post","form-fields":"myfieldsarray"}');

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }

    public function testGetRedirectWhenSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)->willReturn(true);

        /** @var Session $sessionMock */
        $redirect = new Callback($this->context, $sessionMock);
        $result = $redirect->execute();

        /** @var \PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with('{"redirect-url":"http:\/\/redir.ect","form-url":null,"form-method":null,"form-fields":null}');

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }

    public function testGetRedirectWhenNotSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->session;
        $sessionMock->method(self::HAS_REDIRECT_URL)->willReturn(false);

        /** @var Session $sessionMock */
        $redirect = new Callback($this->context, $sessionMock);
        $result = $redirect->execute();

        /** @var \PHPUnit_Framework_MockObject_MockObject $responseMock */
        $responseMock = $this->response;
        $responseMock->expects($this->once())
            ->method(self::RESPONSE_JSON)
            ->with('{"redirect-url":"http:\/\/magen.to\/frontend\/redirect","form-url":null,"form-method":null,"form-fields":null}');

        /** @var $responseMock ResponseInterface */
        $result->renderResult($responseMock);
    }
}
