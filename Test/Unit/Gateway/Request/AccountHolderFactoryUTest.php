<?php

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\AddressFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

class AccountHolderFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $address;

    private $addressFactory;

    public function setUp()
    {
        $this->address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->address->method('getEmail')->willReturn('test@example.com');
        $this->address->method('getFirstname')->willReturn('Joe');
        $this->address->method('getLastname')->willReturn('Doe');
        $this->address->method('getTelephone')->willReturn('00433165349753');

        $this->addressFactory = $this->getMockBuilder(AddressFactory::class)->getMock();
        $this->addressFactory->method('create')->willReturn(new Address('', '', ''));
    }

    public function testCreate()
    {
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory);

        $expected = new AccountHolder();
        $expected->setAddress(new Address('', '', ''));
        $expected->setEmail('test@example.com');
        $expected->setFirstName('Joe');
        $expected->setLastName('Doe');
        $expected->setPhone('00433165349753');

        $this->assertEquals($expected, $accountHolderFactory->create($this->address));
    }

    public function testCreateWithDob()
    {
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory);

        $expected = new AccountHolder();
        $expected->setAddress(new Address('', '', ''));
        $expected->setEmail('test@example.com');
        $expected->setFirstName('Joe');
        $expected->setLastName('Doe');
        $expected->setPhone('00433165349753');
        $expected->setDateOfBirth(new \DateTime('1973-12-07'));

        $this->assertEquals($expected, $accountHolderFactory->create($this->address, '1973-12-07'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateThrowsException()
    {
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory);
        $accountHolderFactory->create(null);
    }
}
