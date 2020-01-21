<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Wirecard\ElasticEngine\Gateway\Request\AccountHolderFactory;
use Wirecard\ElasticEngine\Gateway\Request\AddressFactory;
use Wirecard\ElasticEngine\Gateway\Validator\AddressAdapterInterfaceValidator;
use Wirecard\ElasticEngine\Gateway\Validator\AddressValidatorFactory;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

class AccountHolderFactoryUTest extends \PHPUnit_Framework_TestCase
{
    private $address;

    private $addressFactory;

    private $addressInterfaceValidator;

    private $validatorFactory;

    public function setUp()
    {
        $this->address = $this->getMockBuilder(AddressAdapterInterface::class)->disableOriginalConstructor()->getMock();
        $this->address->method('getEmail')->willReturn('test@example.com');
        $this->address->method('getFirstname')->willReturn('Joe');
        $this->address->method('getLastname')->willReturn('Doe');
        $this->address->method('getTelephone')->willReturn('00433165349753');

        $this->addressFactory = $this->getMockBuilder(AddressFactory::class)->getMock();
        $this->addressFactory->method('create')->willReturn(new Address('', '', ''));

        $this->addressInterfaceValidator = $this->getMockBuilder(AddressAdapterInterfaceValidator::class)->disableOriginalConstructor()->getMock();
        $this->addressInterfaceValidator->method('validate')->willReturn(true);

        $this->validatorFactory = $this->getMockBuilder(AddressValidatorFactory::class)->disableOriginalConstructor()->getMock();
        $this->validatorFactory->method('create')->willReturn($this->addressInterfaceValidator);
    }

    public function testCreate()
    {
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory, $this->validatorFactory);

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
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory, $this->validatorFactory);

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
        $validatorFactory = new AddressValidatorFactory();
        $validator = $validatorFactory->create(null);
        $accountHolderFactory = new AccountHolderFactory($this->addressFactory, $validator);
        $accountHolderFactory->create(null);
    }
}
