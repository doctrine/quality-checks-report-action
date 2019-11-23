#!/bin/bash

composer install --prefer-dist --no-progress

if [ -f "/github/workspace/vendor/bin/phpcs" ];
then
    ./vendor/bin/phpcs --report-checkstyle=/tmp/phpcs.xml
fi

php /app/report.php

git branch -a #debug
