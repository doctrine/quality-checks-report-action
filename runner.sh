#!/bin/bash

set -xe

cd $GITHUB_WORKSPACE

composer install --prefer-dist --no-progress

if [ -f "$GITHUB_WORKSPACE/vendor/bin/phpcs" ];
then
    ./vendor/bin/phpcs --report-checkstyle=/tmp/phpcs.xml
fi

php /app/report.php
