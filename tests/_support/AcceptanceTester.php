<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */

use Page\Checkout as CheckoutPage;
use Page\OrderReceived as OrderReceivedPage;
use Page\Payment as PaymentPage;
use Page\Product3DS as Product3DSPage;
use Page\ProductNon3DS as ProductNon3DSPage;
use Page\Verified as VerifiedPage;
use Wirecard\ElasticEngine\tests\_support\ActorExtendedWithWrappers as ActorExtendedWithWrappers;

class AcceptanceTester extends ActorExtendedWithWrappers
{
    use _generated\AcceptanceTesterActions;

    /**
     * @var string
     * @since 1.4.1
     */
    private $currentPage;

    /**
     * @var array
     * @since 2.0.0
     */
    //Mapping different namings in plugin (because payment action named different in different place)
    private $mappedPaymentActions = [
        'config' => [
            'authorize' => 'authorize',
            'purchase' => 'authorize_capture',
        ],
        'tx_table' => [
            'authorize' => 'authorization',
            'purchase' => 'capture'
        ]
    ];

    /**
     * Method selectPage
     *
     * @param string $name
     * @return Base
     *
     * @since   1.4.1
     */
    private function selectPage($name)
    {
        switch ($name) {
            case 'Checkout':
                $page = new CheckoutPage($this);
                break;
            case 'Product3DS':
                $page = new Product3DSPage($this);
                break;
            case 'ProductNon3DS':
                $page = new ProductNon3DSPage($this);
                break;
            case 'Verified':
                $page = new VerifiedPage($this);
                break;
            case 'Order Received':
                $page = new OrderReceivedPage($this);
                break;
            case 'Payment':
                $page = new PaymentPage($this);
                break;
            default:
                $page = null;
        }
        return $page;
    }

    /**
     * Method getPageElement
     *
     * @param string $elementName
     * @return string
     *
     * @since   1.4.1
     */
    private function getPageElement($elementName)
    {
        //Takes the required element by it's name from required page
        return $this->currentPage->getElement($elementName);
    }

    /**
     * Method waitUntilLoaded
     * @param integer $maxTimeout
     * @param array $function
     * @param array $functionArgs
     * @since   2.2.0
     */
    protected function waitUntil($maxTimeout=80, array $function = null, array $functionArgs = null)
    {
        $counter = 0;
        while ($counter <= $maxTimeout) {
            $this->wait(1);
            $counter++;
            if ($function != null) {
                if (call_user_func($function, $functionArgs)) {
                    break;
                }
            }
        }
    }

    /**
     * Method checkPaymentActionInTransactionTable
     *
     * @param string $paymentAction
     * @return boolean
     * @since   2.2.0
     */
    protected function checkPaymentActionInTransactionTable($paymentAction)
    {
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria('sales_payment_transaction', 'txn_type');
        $tempTxType = $this->mappedPaymentActions['tx_table'][$paymentAction[0]];
        if (end($transactionTypes) == $tempTxType) {
            return true;
        }
        return false;
    }

    /**
     * Method waitUntilPageLoaded
     * @since   2.2.0
     * @return boolean
     */
    public function waitUntilPageLoaded()
    {
        $currentUrl = $this->grabFromCurrentUrl();
        if ($currentUrl != '' && $this->currentPage->getPageSpecific() != '') {
            return false;
        }
        if (strpos($currentUrl, $this->currentPage->getPageSpecific()) != false) {
            $this->wait(3);
            return true;
        }
        return false;
    }

    /**
     * @Given I am on :page page
     * @param string $page
     * @since 1.4.1
     */
    public function iAmOnPage($page)
    {
        // Open the page and initialize required pageObject
        $this->currentPage = $this->selectPage($page);
        $this->amOnPage($this->currentPage->getURL());
        $this->waitUntil(80, [$this, 'waitUntilPageLoaded']);
    }

    /**
     * @When I click :object
     * @param string $object
     * @since 1.4.1
     */
    public function iClick($object)
    {
        $this->preparedClick($this->getPageElement($object));
    }

    /**
     * @When I am redirected to :page page
     * @param string $page
     * @since 1.4.1
     */
    public function iAmRedirectedToPage($page)
    {
        // Initialize required pageObject WITHOUT checking URL
        $this->currentPage = $this->selectPage($page);
        $this->waitUntil(80, [$this, 'waitUntilPageLoaded']);
        // Check only specific keyword that page URL should contain
        $this->seeInCurrentUrl($this->currentPage->getPageSpecific());
    }

    /**
     * @When I fill fields with :data
     * @param string $data
     * @since 1.4.1
     */
    public function iFillFieldsWith($data)
    {
        $this->fillFieldsWithData($data, $this->currentPage);
    }

    /**
     * @When I enter :fieldValue in field :fieldID
     * @param string $fieldValue
     * @param string $fieldID
     * @since 1.4.1
     */
    public function iEnterInField($fieldValue, $fieldID)
    {
        $this->preparedFillField($this->getPageElement($fieldID), $fieldValue);
    }

    /**
     * @Then I see :text
     * @param string $text
     * @since 1.4.1
     */
    public function iSee($text)
    {
        $this->see($text);
    }

    /**
     * @Given I prepare checkout :type
     * @param string $type
     * @since 1.4.1
     */
    public function iPrepareCheckout($type)
    {
        $page = 'Product3DS';
        if (strpos($type, 'Non3DS') !== false) {
            $page = 'ProductNon3DS';
        }
        $this->iAmOnPage($page);
        $this->currentPage->prepareCheckout();
    }

    /**
     * @When I check :box
     * @param string $box
     * @since 1.4.1
     */
    public function iCheck($box)
    {
        $this->currentPage->checkBox($box);
    }

    /**
     * @Given I activate payment action :paymentAction in configuration
     * @param string $paymentAction
     * @since 2.0.0
     */
    public function iActivatePaymentActionInConfiguration($paymentAction)
    {
        $this->updateInDatabase(
            'core_config_data',
            ['value' => $this->mappedPaymentActions['config'][$paymentAction]],
            ['path' => 'payment/wirecard_elasticengine_creditcard/payment_action']
        );
        //clean magento2 cache to for changes in database to come in place
        exec("docker exec -it " . getenv("MAGENTO_CONTAINER_NAME") . " php bin/magento cache:clean");
        exec("docker exec -it " . getenv("MAGENTO_CONTAINER_NAME") . " php bin/magento cache:flush");
    }

    /**
     * @Then I see :paymentAction in transaction table
     * @param string $paymentAction
     * @since 2.0.0
     */
    public function iSeeInTransactionTable($paymentAction)
    {
        $this->waitUntil(60, [$this, 'checkPaymentActionInTransactionTable'], [$paymentAction]);
        $this->assertEquals($this->checkPaymentActionInTransactionTable([$paymentAction]), true);
    }
}
