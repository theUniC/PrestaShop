<?php

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Before running any unit test you must install the dependencies through composer!');
}

$_GET['id_shop'] = '1';

require __DIR__ . '/../config/config.inc.php';