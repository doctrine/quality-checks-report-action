#!/bin/bash

echo "PWD: $PWD\n"
CURRENT=`dirname $0`

cd $CURRENT
composer install

cd -
php /app/runner.php
