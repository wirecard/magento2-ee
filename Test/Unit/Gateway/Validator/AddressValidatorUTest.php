<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Validator;

use Magento\Quote\Model\Quote\Address;
use Wirecard\ElasticEngine\Gateway\Validator\AddressValidator;

class AddressValidatorUTest extends \PHPUnit_Framework_TestCase
{
    private $magentoQuoteAddress;

    private $validator;

    public function setUp()
    {
        $this->magentoQuoteAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $this->validator = new AddressValidator();
    }

    public function testValidAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn('AT');
        $this->magentoQuoteAddress->method('getCity')->willReturn('Testcity');
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn('Teststreet 1');

        $this->assertTrue($this->validator->validate(['addressObj' => $this->magentoQuoteAddress]));
    }

    public function testEmptyAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn('');
        $this->magentoQuoteAddress->method('getCity')->willReturn('');
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn('');

        $this->assertFalse($this->validator->validate(['addressObj' => $this->magentoQuoteAddress]));
    }

    public function testInvalidAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn(null);
        $this->magentoQuoteAddress->method('getCity')->willReturn(null);
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn(null);

        $this->assertFalse($this->validator->validate(['addressObj' => $this->magentoQuoteAddress]));
    }
}
