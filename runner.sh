#!/bin/bash

CURRENT=`dirname $0`

cd $CURRENT
composer install
php runner.php
