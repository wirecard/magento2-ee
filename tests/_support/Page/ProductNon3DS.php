<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class ProductNon3DS extends Product3DS
{

    /**
     * @var string url of current page
     * @since 1.5.3
     */
    public $URL = '/index.php/savvy-shoulder-tote.html';
    /**
     * Method prepareCheckout
     *
     * @return string
     *
     * @since 1.5.3
     */
    public function prepareCheckout()
    {
        parent::prepareCheckout();
    }
}
