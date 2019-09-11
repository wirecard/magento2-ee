<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use stdClass;
use Wirecard\ElasticEngine\Gateway\Helper\NestedObject;

class NestedObjectUTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NestedObject
     */
    protected $helper;

    public function setUp()
    {
        $this->helper = new NestedObject();
    }

    public function testGet()
    {
        $object = (object)[
            'foo' => 'bar'
        ];

        $this->assertEquals('bar', $this->helper->get($object, 'foo'));
    }

    public function testGetNoObject()
    {
        $this->assertNull($this->helper->get(null, 'foo'));
    }

    public function testGetPropertyNotFound()
    {
        $this->assertNull($this->helper->get(new stdClass(), 'foo'));
    }

    public function testGetIn()
    {
        $object = (object)[
            'foo' => (object)[
                'bar' => 'com'
            ]
        ];

        $this->assertEquals('com', $this->helper->getIn($object, ['foo', 'bar']));
    }

    public function testGetInNotFound1()
    {
        $object = (object)[
            'fooX' => (object)[
                'bar' => 'com'
            ]
        ];

        $this->assertNull($this->helper->getIn($object, ['foo', 'bar']));
    }

    public function testGetInNotFound2()
    {
        $object = (object)[
            'foo' => (object)[
                'barX' => 'com'
            ]
        ];

        $this->assertNull($this->helper->getIn($object, ['foo', 'bar']));
    }

    public function testGetInReturnSubObject()
    {
        $object = (object)[
            'foo' => (object)[
                'bar' => 'com'
            ]
        ];

        $this->assertEquals($object->foo, $this->helper->getIn($object, ['foo']));
    }
}
