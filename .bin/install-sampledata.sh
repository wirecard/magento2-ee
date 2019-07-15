#!/usr/bin/env bash
# Shop System SDK:
# - Terms of Use can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
# - License can be found under:
# https://github.com/wirecard/magento2-ee/blob/master/LICENSE

su www-data <<EOSU
ln -s ~/.composer/auth.json /var/www/html/var/composer_home/
/var/www/html/bin/magento sampledata:deploy
/var/www/html/bin/magento setup:upgrade
/var/www/html/bin/magento setup:di:compile
EOSU