<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Sil\PhpEnv\Env;

$mysqlHost = Env::get('MYSQL_HOST');
$mysqlDatabase = Env::get('MYSQL_DATABASE');
$mysqlUser = Env::get('MYSQL_USER');
$mysqlPassword = Env::get('MYSQL_PASSWORD');

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $mysqlHost,
    'database'  => $mysqlHost,
    'username'  => $mysqlUser,
    'password'  => $mysqlPassword,
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
