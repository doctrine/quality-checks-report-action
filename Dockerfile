FROM php:7.3-cli

WORKDIR '/app'

RUN set -eux; apt-get update; apt-get install -y libzip-dev zlib1g-dev; docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | \
            php -- --install-dir=/usr/bin/ --filename=composer

ADD ["composer.json", "composer.lock", "runner.sh", "report.php", "vendor", "/app/"]

RUN composer install --prefer-dist

CMD ["/bin/bash", "/app/runner.sh"]
