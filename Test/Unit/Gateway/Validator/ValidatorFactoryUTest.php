<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Validator;
use Wirecard\ElasticEngine\Gateway\Validator\AddressAdapterInterfaceValidator;
use Wirecard\ElasticEngine\Gateway\Validator\QuoteAddressValidator;
use Wirecard\ElasticEngine\Gateway\Validator\ValidatorFactory;

class ValidatorFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $quoteAddressValidator;

    private $addressAdapterInterfaceValidator;

    public function setUp()
    {
        $this->quoteAddressValidator = $this->getMockBuilder(QuoteAddressValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressAdapterInterfaceValidator = $this->getMockBuilder(AddressAdapterInterfaceValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateQuoteAddressValidator()
    {
        $magentoQuoteAddress = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expected = new QuoteAddressValidator($magentoQuoteAddress);

        $factory = new ValidatorFactory();
        $actual = $factory->create(Validator::QUOTE_ADDRESS, $magentoQuoteAddress);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateAddressAdapterInterfaceValidator()
    {
        $magentoAddressAdapterInterface = $this->getMockBuilder(AddressAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $expected = new AddressAdapterInterfaceValidator($magentoAddressAdapterInterface);

        $factory = new ValidatorFactory();
        $actual = $factory->create(Validator::ADDRESS_ADAPTER_INTERFACE, $magentoAddressAdapterInterface);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid validator given
     */
    public function testCreateInvalidValidator()
    {
        $magentoQuoteAddress = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory = new ValidatorFactory();
        $actual = $factory->create('Invalid', $magentoQuoteAddress);
    }
}
