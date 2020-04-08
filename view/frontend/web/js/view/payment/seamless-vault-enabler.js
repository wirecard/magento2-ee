/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */
define([
        "Magento_Vault/js/view/payment/vault-enabler",
    ],
    function (VaultEnabler) {
        return VaultEnabler.extend({
            defaults: {
                isActivePaymentTokenEnabler: false
            }
        });
    }
);
