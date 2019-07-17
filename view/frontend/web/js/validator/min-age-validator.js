/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'jquery',
        'mage/validation'
    ],
    function ($) {
        'use strict';

        return {
            /**
             * Validate checkout agreements
             *
             * @returns {Boolean}
             */
            validate: function (dob) {

                var birthdate = new Date(dob);

                var year = birthdate.getFullYear();
                var today = new Date();
                if (year <= 1899 || year >= today.getFullYear() + 1) {
                    return false;
                }

                var limit = new Date((today.getFullYear() - 18), today.getMonth(), today.getDate());
                return birthdate < limit;
            }
        };
    }
);
