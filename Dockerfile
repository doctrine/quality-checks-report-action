FROM php:7.3-cli

WORKDIR '/app'

RUN curl -sS https://getcomposer.org/installer | \
            php -- --install-dir=/usr/bin/ --filename=composer

CMD ["/bin/bash", "runner.sh"]
