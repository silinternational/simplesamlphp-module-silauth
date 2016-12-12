<?php

use Sil\PhpEnv\Env;

return [
    'basePath' => __DIR__ . '/../',
    'id' => 'SilAuth',
    'aliases' => [
        '@Sil/SilAuth' => __DIR__ . '/..',
    ],
    'bootstrap' => [
        'gii',
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => sprintf(
                'mysql:host=%s;dbname=%s',
                Env::get('MYSQL_HOST'),
                Env::get('MYSQL_DATABASE')
            ),
            'username' => Env::get('MYSQL_USER'),
            'password' => Env::get('MYSQL_PASSWORD'),
        ],
    ],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
        ],
    ],
];
