<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Wirecard\ElasticEngine\Controller\Frontend\Redirect;

class RedirectUTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRedirect()
    {
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRedirectUrl'])
            ->getMock();
        $session->method('getRedirectUrl')->willReturn('http://redir.ect');
        $resultFactory = $this->getMOckBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setJsonData'])
            ->getMock();
        $resultFactory->method('create')->willReturn($json);
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResultFactory'])
            ->getMock();
        $context->method('getResultFactory')->willReturn($resultFactory);

        /**
         * @var Context $context
         * @var Session $session
         */
        $redirect = new Redirect($context, $session);

        $result = $redirect->execute();

        $this->assertEquals($json, $result);
    }
}