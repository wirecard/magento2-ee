<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Request\AddressFactory;
use Wirecard\PaymentSdk\Entity\Address;

class AddressFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $address;

    public function setUp()
    {
        $this->address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->address->method('getCountryId')->willReturn('AT');
        $this->address->method('getCity')->willReturn('Graz');
        $this->address->method('getStreetLine1')->willReturn('Reininghausstraße 13a');
        $this->address->method('getPostcode')->willReturn('8020');
        $this->address->method('getStreetLine2')->willReturn('blub');
    }

    public function testCreate()
    {
        $addressFactory = new AddressFactory();

        $expected = new Address('AT', 'Graz', 'Reininghausstraße 13a');
        $expected->setPostalCode('8020');
        $expected->setStreet2('blub');

        $this->assertEquals($expected, $addressFactory->create($this->address));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $addressFactory = new AddressFactory();
        $addressFactory->create(null);
    }
}
