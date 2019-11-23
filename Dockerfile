FROM php:7.3-cli

WORKDIR '/app'

RUN curl -sS https://getcomposer.org/installer | \
            php -- --install-dir=/usr/bin/ --filename=composer

ADD ["composer.json", "runner.sh", "runner.php", "/app"]

CMD ["/bin/bash", "runner.sh"]
