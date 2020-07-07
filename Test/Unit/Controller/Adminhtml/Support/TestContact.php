<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace WirecardTest\ElasticEngine\Unit\Controller\Adminhtml\Support;

use Wirecard\ElasticEngine\Controller\Adminhtml\Support\Contact;

/**
 * Test contact for unit testing
 *
 * Class TestContact
 */
class TestContact extends Contact
{
    public function testIsAllowed($authorization)
    {
        $this->_authorization = $authorization;
        return $this->_isAllowed();
    }
}
