<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Validator\AddressAdapterInterfaceValidator;

class AddressAdapterInterfaceValidatorUTest extends \PHPUnit_Framework_TestCase
{
    private $magentoAddressInterface;

    public function setUp()
    {
        $this->magentoAddressInterface = $this->getMockBuilder(AddressAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidAddressObject()
    {
        $this->magentoAddressInterface->method('getCountryId')
            ->willReturn('AT');
        $this->magentoAddressInterface->method('getCity')
            ->willReturn('Testcity');
        $this->magentoAddressInterface->method('getStreetLine1')
            ->willReturn('Teststreet 1');

        $actual = new AddressAdapterInterfaceValidator($this->magentoAddressInterface);
        $this->assertTrue($actual->validate());
    }

    public function testEmptyAddressObject()
    {
        $this->magentoAddressInterface->method('getCountryId')
            ->willReturn('');
        $this->magentoAddressInterface->method('getCity')
            ->willReturn('');
        $this->magentoAddressInterface->method('getStreetLine1')
            ->willReturn('');

        $actual = new AddressAdapterInterfaceValidator($this->magentoAddressInterface);
        $this->assertFalse($actual->validate());
    }

    public function testInvalidAddressObject()
    {
        $this->magentoAddressInterface->method('getCountryId')
            ->willReturn(null);
        $this->magentoAddressInterface->method('getCity')
            ->willReturn(null);
        $this->magentoAddressInterface->method('getStreetLine1')
            ->willReturn(null);

        $actual = new AddressAdapterInterfaceValidator($this->magentoAddressInterface);
        $this->assertFalse($actual->validate());
    }
}
