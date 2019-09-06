<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Wirecard\ElasticEngine\Gateway\Model\Notify as NotifyModel;
use Wirecard\ElasticEngine\Gateway\Model\TransactionUpdater;

/**
 * Class Notify
 *
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @method Http getRequest()
 */
class Notify extends Action implements CsrfAwareActionInterface
{
    use NoCsrfTrait;

    /**
     * @var NotifyModel
     */
    protected $notify;

    /**
     * @var TransactionUpdater
     */
    protected $transactionUpdater;

    /**
     * Notify constructor.
     *
     * @param Context $context
     * @param NotifyModel $notify
     * @param TransactionUpdater $transactionUpdater
     */
    public function __construct(
        Context $context,
        NotifyModel $notify,
        TransactionUpdater $transactionUpdater
    ) {
        $this->notify             = $notify;
        $this->transactionUpdater = $transactionUpdater;

        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        //get the raw request body
        $payload  = $this->getRequest()->getContent();
        if (strlen($payload)) {
            $response = $this->notify->fromXmlResponse($payload);
            $this->notify->process($response);
        }
        //$this->transactionUpdater->run();
    }
}
