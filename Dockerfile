FROM php:7.1-apache

ARG MAGENTO_VERSION
ENV MAGENTO_VERSION=$MAGENTO_VERSION
ENV INSTALL_DIR /var/www/html
ENV COMPOSER_HOME /var/www/.composer/

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer
COPY ./.bin/auth.json $COMPOSER_HOME

RUN requirements="libpng-dev libmcrypt-dev libmcrypt4 libcurl3-dev libfreetype6 libjpeg62-turbo libjpeg62-turbo-dev libpng-dev libfreetype6-dev libicu-dev libxslt1-dev unzip cron" \
    && apt-get -qq update \
    && apt-get -qq install -y $requirements \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install zip \
    && docker-php-ext-install intl \
    && docker-php-ext-install xsl \
    && docker-php-ext-install soap \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install mysqli && docker-php-ext-enable mysqli \
    && requirementsToRemove="libpng-dev libmcrypt-dev libcurl3-dev libfreetype6-dev libjpeg62-turbo-dev" \
    && apt-get purge --auto-remove -y $requirementsToRemove

RUN apt-get update \
    && apt-get install -y libmcrypt-dev \
    && docker-php-ext-install  mcrypt

RUN chsh -s /bin/bash www-data

RUN cd /tmp && \
  curl https://codeload.github.com/magento/magento2/tar.gz/$MAGENTO_VERSION -o $MAGENTO_VERSION.tar.gz && \
  tar xf $MAGENTO_VERSION.tar.gz && \
  mv magento2-$MAGENTO_VERSION/* magento2-$MAGENTO_VERSION/.htaccess $INSTALL_DIR

RUN chown -R www-data:www-data /var/www
RUN su www-data -c "cd $INSTALL_DIR && composer install"
RUN su www-data -c "cd $INSTALL_DIR && composer config repositories.magento composer https://repo.magento.com/"

RUN cd $INSTALL_DIR \
    && find . -type d -exec chmod 770 {} \; \
    && find . -type f -exec chmod 660 {} \; \
    && chmod u+x bin/magento

COPY ./.bin/install-magento /usr/local/bin/install-magento
RUN chmod +x /usr/local/bin/install-magento

COPY ./.bin/install-sampledata /usr/local/bin/install-sampledata
RUN chmod +x /usr/local/bin/install-sampledata

RUN a2enmod rewrite
RUN echo "memory_limit=2048M" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR $INSTALL_DIR

# Add cron job
ADD ./.bin/crontab /etc/cron.d/magento2-cron
RUN chmod 0644 /etc/cron.d/magento2-cron \
    && crontab -u www-data /etc/cron.d/magento2-cron