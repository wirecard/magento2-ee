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

use Page\Base;
use Page\Checkout as CheckoutPage;
use Page\OrderReceived as OrderReceivedPage;
use Page\Payment as PaymentPage;
use Page\Product3DS as Product3DSPage;
use Page\ProductNon3DS as ProductNon3DSPage;
use Page\Verified as VerifiedPage;

class AcceptanceTester extends \Codeception\Actor
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
                $this->wait(10);
                $page = new CheckoutPage($this);
                break;
            case 'Product3DS':
                $page = new Product3DSPage($this);
                break;
            case 'ProductNon3DS':
                $page = new ProductNon3DSPage($this);
                break;
            case 'Verified':
                $this->wait(45);
                $page = new VerifiedPage($this);
                break;
            case 'Order Received':
                $this->wait(45);
                $page = new OrderReceivedPage($this);
                break;
            case 'Payment':
                $this->wait(45);
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
     * @Given I am on :page page
     * @param string $page
     * @since 1.4.1
     */
    public function iAmOnPage($page)
    {
        // Open the page and initialize required pageObject
        $this->currentPage = $this->selectPage($page);
        $this->amOnPage($this->currentPage->getURL());
    }

    /**
     * @When I click :object
     * @param string $object
     * @since 1.4.1
     */
    public function iClick($object)
    {
        $this->waitForElementVisible($this->getPageElement($object));
        $this->waitForElementClickable($this->getPageElement($object));
        $this->click($this->getPageElement($object));
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
        $this->waitForElementVisible($this->getPageElement($fieldID));
        $this->fillField($this->getPageElement($fieldID), $fieldValue);
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
        $this->seeInDatabase(
            'sales_payment_transaction',
            ['txn_type like' => $this->mappedPaymentActions['tx_table'][$paymentAction]]
        );
        //check that last transaction in the table is the one under test
        $transactionTypes = $this->getColumnFromDatabaseNoCriteria('sales_payment_transaction', 'txn_type');
        $this->assertEquals(end($transactionTypes), $this->mappedPaymentActions['tx_table'][$paymentAction]);
    }
}
