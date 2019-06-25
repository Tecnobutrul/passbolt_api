FROM php:7.2-fpm

LABEL maintainer="diego@passbolt.com"


ARG PHP_EXTENSIONS="gd \
      intl \
      ldap \
      pdo_mysql \
      opcache \
      xsl"

ARG PECL_PASSBOLT_EXTENSIONS="gnupg \
      redis \
      mcrypt"

ARG REDIS_PINNED_VERSION="4.3.0"

ARG PASSBOLT_DEV_PACKAGES="libgpgme11-dev \
      libpng-dev \
      libjpeg62-turbo-dev \
      libicu-dev \
      libldap2-dev \
      libxslt1-dev \
      libmcrypt-dev \
      unzip \
      git"

ENV PECL_BASE_URL="https://pecl.php.net/get"
ENV PHP_EXT_DIR="/usr/src/php/ext"
ENV NR_VERSION="8.7.0.242"
ENV NR_URL="https://download.newrelic.com/php_agent/release/newrelic-php5-${NR_VERSION}-linux.tar.gz"

COPY --chown=www-data:www-data . /var/www/passbolt

WORKDIR /var/www/passbolt
RUN apt-get update \
    && apt-get -y install --no-install-recommends $PASSBOLT_DEV_PACKAGES \
         nginx \
         gnupg \
         libgpgme11 \
         libmcrypt4 \
         mysql-client \
         supervisor \
         cron \
    && curl -L $NR_URL | tar -C /tmp -zx \
    && NR_INSTALL_USE_CP_NOT_LN=1 NR_INSTALL_SILENT=1 /tmp/newrelic-php5-*/newrelic-install install \
    && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall* \
    && mkdir /home/www-data \
    && chown -R www-data:www-data /home/www-data \
    && usermod -d /home/www-data www-data \
    && docker-php-source extract \
    && for i in $PECL_PASSBOLT_EXTENSIONS; do \
         mkdir $PHP_EXT_DIR/$i; \
         curl -sSL $PECL_BASE_URL/$i | tar zxf - -C $PHP_EXT_DIR/$i --strip-components 1; \
       done \
    && PECL_PASSBOLT_EXTENSIONS_SLUGS="$(echo $PECL_PASSBOLT_EXTENSIONS | sed -e 's/-$REDIS_PINNED_VERSION//')" \
    && docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j4 $PHP_EXTENSIONS $PECL_PASSBOLT_EXTENSIONS_SLUGS \
    && docker-php-ext-enable $PHP_EXTENSIONS $PECL_PASSBOLT_EXTENSIONS_SLUGS \
    && docker-php-source delete \
    && EXPECTED_SIGNATURE=$(curl -s https://composer.github.io/installer.sig) \
    && curl -o composer-setup.php https://getcomposer.org/installer \
    && ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');") \
    && if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
         >&2 echo 'ERROR: Invalid installer signature'; \
         rm composer-setup.php; \
         exit 1; \
       fi \
    && php composer-setup.php \
    && rm composer-setup.php \
    && mv composer.phar /usr/local/bin/composer \
    && composer install -n --no-dev --optimize-autoloader \
    && chown -R www-data:www-data . \
    && chmod 775 $(find /var/www/passbolt/tmp -type d) \
    && chmod 664 $(find /var/www/passbolt/tmp -type f) \
    && chmod 775 $(find /var/www/passbolt/webroot/img/public -type d) \
    && chmod 664 $(find /var/www/passbolt/webroot/img/public -type f) \
    && rm /etc/nginx/sites-enabled/default \
    && apt-get purge -y --auto-remove $PASSBOLT_DEV_PACKAGES \
    && echo 'php_flag[expose_php] = off' > /usr/local/etc/php-fpm.d/expose.conf \
    && rm -rf /var/lib/apt/lists/* \
    && rm /usr/local/bin/composer \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/conf/passbolt.conf /etc/nginx/conf.d/default.conf
COPY docker/conf/nginx.conf /etc/nginx/nginx.conf
COPY docker/conf/supervisor/*.conf /etc/supervisor/conf.d/
COPY docker/bin/docker-entrypoint.sh /docker-entrypoint.sh

RUN rm -rf docker

EXPOSE 80 443

CMD ["/docker-entrypoint.sh"]
