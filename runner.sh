#!/bin/bash

cd $GITHUB_WORKSPACE

composer install --prefer-dist --no-progress -q

if [ -f "$GITHUB_WORKSPACE/vendor/bin/phpcs" ];
then
    ./vendor/bin/phpcs --report-checkstyle=/tmp/phpcs.xml
fi

git log -n 5

php /app/report.php
exit $?
