#!/bin/bash

echo "PWD: $PWD\n"
CURRENT=`dirname $0`

cd $CURRENT
composer install --prefer-dist

cd -
composer install --prefer-dist

if [ -f "/github/workspace/vendor/bin/phpcs" ];
then
    ./vendor/bin/phpcs --report-checkstyle=/tmp/phpcs.xml
fi

php /app/report.php
