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
use Wirecard\ElasticEngine\Gateway\Validator\QuoteAddressValidator;

class QuoteAddressValidatorUTest extends \PHPUnit_Framework_TestCase
{
    private $magentoQuoteAddress;

    public function setUp()
    {
        $this->magentoQuoteAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
    }

    public function testValidAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn('AT');
        $this->magentoQuoteAddress->method('getCity')->willReturn('Testcity');
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn('Teststreet 1');

        $actual = new QuoteAddressValidator($this->magentoQuoteAddress);
        $this->assertTrue($actual->validate());
    }

    public function testEmptyAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn('');
        $this->magentoQuoteAddress->method('getCity')->willReturn('');
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn('');

        $actual = new QuoteAddressValidator($this->magentoQuoteAddress);
        $this->assertFalse($actual->validate());
    }

    public function testInvalidAddressObject()
    {
        $this->magentoQuoteAddress->method('getCountryId')->willReturn(null);
        $this->magentoQuoteAddress->method('getCity')->willReturn(null);
        $this->magentoQuoteAddress->method('getStreetLine')->willReturn(null);

        $actual = new QuoteAddressValidator($this->magentoQuoteAddress);
        $this->assertFalse($actual->validate());
    }
}
