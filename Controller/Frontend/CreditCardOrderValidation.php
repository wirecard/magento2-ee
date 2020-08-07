<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Wirecard\ElasticEngine\Controller\Frontend;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;

/**
 * Class CreditCardOrderValidation
 * @package Wirecard\ElasticEngine\Controller\Frontend
 * @since 3.1.6
 */
class CreditCardOrderValidation extends Action
{
    /** @var string FORM parameter name to send the renderd form amount */
    const FRONTEND_AMOUNT_KEY = 'rendered-form-amount';

    /** @var string KEY for the javascript to validate */
    const FRONTEND_VALIDATION_KEY = 'sessionValid';

    /** @var string Magento2 grand total key */
    const MAGENTO_GRANT_TOTAL = 'grand_total';

    /** @var string Magento2 value key */
    const MAGENTO_VALUE = 'value';

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var Session */
    private $checkoutSession;

    /**
     * CreditCardOrderValidation constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory, Session $checkoutSession)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        /** @var Total $totals */
        $totals = $quote->getTotals();

        $params = $this->getRequest()->getParams();
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(200);
        $result->setData([
            self::FRONTEND_VALIDATION_KEY => $this->isValidRenderdAmount(
                $totals[self::MAGENTO_GRANT_TOTAL][self::MAGENTO_VALUE],
                $params[self::FRONTEND_AMOUNT_KEY]
            )
        ]);

        return $result;
    }

    /**
     * If true is returned the session amount is same as the rendered amount
     *
     * @param float $renderedAmount
     * @param float $sessionAmount
     * @return bool
     */
    private function isValidRenderdAmount($renderedAmount, $sessionAmount)
    {
        $diff = abs($sessionAmount - $renderedAmount);
        return !($diff > 0.001);
    }
}
