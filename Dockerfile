FROM php:7.3-cli

WORKDIR '/app'

RUN curl -sS https://getcomposer.org/installer | \
            php -- --install-dir=/usr/bin/ --filename=composer

RUN composer install

CMD ["php", "runner.php"]
