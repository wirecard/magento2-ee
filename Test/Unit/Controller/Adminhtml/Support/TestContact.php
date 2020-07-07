<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Adminhtml\Support;

use Wirecard\ElasticEngine\Controller\Adminhtml\Support\Contact;

class TestContact extends Contact
{
    public function testIsAllowed($authorization)
    {
        $this->_authorization = $authorization;
        return $this->_isAllowed();
    }
}
