/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

define(
    [
        "jquery",
        "uiComponent",
        "Magento_Checkout/js/model/payment/renderer-list"
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        "use strict";
        rendererList.push(
            {
                type: "wirecard_elasticengine_paypal",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_creditcard",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/creditcard"
            },
            {
                type: "wirecard_elasticengine_maestro",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/creditcard"
            },
            {
                type: "wirecard_elasticengine_sepadirectdebit",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/sepa"
            },
            {
                type: "wirecard_elasticengine_sofortbanking",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_ideal",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ideal"
            },
            {
                type: "wirecard_elasticengine_giropay",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/giropay"
            },
            {
                type: "wirecard_elasticengine_ratepayinvoice",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ratepay"
            },
            {
                type: "wirecard_elasticengine_ratepayinstall",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ratepay"
            },
            {
                type: "wirecard_elasticengine_alipayxborder",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_poipia",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_masterpass",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_unionpayinternational",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/creditcard"
            },
            {
                type: "wirecard_elasticengine_paybybankapp",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            }
        );

        $.mage.__('heading_title_alipay_crossborder');
        $.mage.__('heading_title_creditcard');
        $.mage.__('heading_title_giropay');
        $.mage.__('heading_title_ideal');
        $.mage.__('heading_title_maestro');
        $.mage.__('heading_title_masterpass');
        $.mage.__('heading_title_paypal');
        $.mage.__('heading_title_poi_pia');
        $.mage.__('heading_title_ratepayinvoice');
        $.mage.__('heading_title_sepact');
        $.mage.__('heading_title_sepadd');
        $.mage.__('heading_title_sofortbanking');
        $.mage.__('heading_title_upi');

        return Component.extend({});
    }
);