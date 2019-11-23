<?php

require_once 'vendor/autoload.php';

echo "Hello world!";

var_dump(array_keys($_SERVER));
var_dump($_SERVER['GITHUB_WORKSPACE']);

echo file_get_contents("/tmp/phpcs.xml");
