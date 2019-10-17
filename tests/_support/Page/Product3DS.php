<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class Product3DS extends Base
{

    /**
     * @var string
     * @since 1.5.1
     */
    // include url of current page
    public $URL = '/index.php/fusion-backpack.html';

    /**
     * @var string
     * @since 2.2.0
     */
    public $pageSpecific = 'backpack';

    /**
     * @var array
     * @since 1.5.1
     */
    public $elements = [
        'Add to Cart' => '//*[@id="product-addtocart-button"]',
        'Basket' => '//*[@class="action showcart"]',
        'Proceed to Checkout' => '//*[@class="action primary checkout"]',
    ];
    /**
     * Method prepareCheckout
     *
     * @since 1.5.3
     */
    public function prepareCheckout()
    {
        $I = $this->tester;
        $I->preparedClick($this->getElement('Add to Cart'));
        $I->wait(10);
        $I->preparedClick(parent::getElement('Basket'));
        $I->preparedClick(parent::getElement('Proceed to Checkout'));
    }
}
