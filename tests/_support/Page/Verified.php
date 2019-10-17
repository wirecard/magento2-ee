<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

namespace Page;

class Verified extends Base
{

    // include url of current page
    /**
     * @var string
     * @since 1.4.1
     */
    public $URL = '/bank';

    /**
     * @var string
     * @since 2.2.0
     */
    public $pageSpecific = 'bank';

    /**
     * @var array
     * @since 1.4.1
     */

    public $elements = [
        'Password' => "//*[@id='password']",
        'Continue' => "//*[@name='authenticate']",
    ];
}
