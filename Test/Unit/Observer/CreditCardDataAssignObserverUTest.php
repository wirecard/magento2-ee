<?php

namespace Wirecard\ElasticEngine\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Wirecard\ElasticEngine\Observer\CreditCardDataAssignObserver;

class CreditCardDataAssignObserverUTest extends \PHPUnit_Framework_TestCase
{
    const GET_DATA = 'getData';
    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var InfoInterface
     */
    private $infoObject;

    /**
     * @var DataObject
     */
    private $dataObject;

    public function setUp()
    {
        $this->observer = $this->getMockWithoutInvokingTheOriginalConstructor(Observer::class);
        $event = $this->getMockWithoutInvokingTheOriginalConstructor(Event::class);

        $this->dataObject = $this->getMockWithoutInvokingTheOriginalConstructor(DataObject::class);
        $this->infoObject = $this->getMockForAbstractClass(InfoInterface::class);

        $event->method('getDataByKey')->withConsecutive(
            [AbstractDataAssignObserver::DATA_CODE],
            [AbstractDataAssignObserver::MODEL_CODE]
        )->willReturnOnConsecutiveCalls($this->dataObject, $this->infoObject);
        $this->observer->method('getEvent')->willReturn($event);
    }

    public function testExecute()
    {
        $dataAssign = new CreditCardDataAssignObserver();

        $this->dataObject->method(self::GET_DATA)->willReturn(['token_id' => 'mytoken']);
        $this->infoObject->expects($this->once())->method('setAdditionalInformation')->with('token_id', 'mytoken');
        $dataAssign->execute($this->observer);
    }

    public function testExecuteWithNoArray()
    {
        $dataAssign = new CreditCardDataAssignObserver();

        $this->dataObject->method(self::GET_DATA)->willReturn('');
        $this->assertEquals(null, $dataAssign->execute($this->observer));
    }

    public function testExecuteWithNoData()
    {
        $dataAssign = new CreditCardDataAssignObserver();

        $this->dataObject->method(self::GET_DATA)->willReturn([]);
        $this->infoObject->expects($this->never())->method('setAdditionalInformation');
        $dataAssign->execute($this->observer);
    }
}
