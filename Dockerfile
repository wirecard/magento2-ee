FROM alexcheng/magento2:2.2.7

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
