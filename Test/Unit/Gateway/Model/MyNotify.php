<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Test\Unit\Gateway\Model;

use Wirecard\ElasticEngine\Gateway\Model\Notify;

/**
 * Test notify class
 */
class MyNotify extends Notify
{
    public function myHandleSuccess($order, $response)
    {
        $this->handleSuccess($order, $response);
    }

    public function myGeneratePublicHash($paymentToken)
    {
        return $this->generatePublicHash($paymentToken);
    }
}
