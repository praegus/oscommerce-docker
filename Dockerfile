FROM php:5.6-apache

ADD ./config/ /

ENV OSCOMMERCE_VERSION=2.3.4.1

RUN apt-get update && apt-get install -y --no-install-recommends mysql-client zip unzip && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) pdo_mysql mysqli pdo mbstring

RUN cd /tmp \
    && curl -LO https://github.com/osCommerce/oscommerce2/archive/v$OSCOMMERCE_VERSION.tar.gz \
    && tar xf v$OSCOMMERCE_VERSION.tar.gz \
    && rm -rf /var/www/html/ \
    && mv oscommerce2-$OSCOMMERCE_VERSION/catalog/ /var/www/html/

RUN chmod +x /*.sh

COPY ./config/configure.php /var/www/html/includes
COPY ./config/configure_admin.php /var/www/html/admin/includes/configure.php
COPY ./config/admin_user.sql /var/www/html/admin/includes
COPY ./config/languages.sql /var/www/html/admin/includes

ENTRYPOINT ["/install.sh"]

#RUN rm -rf /var/www/html/install

CMD ["apache2-foreground"]
