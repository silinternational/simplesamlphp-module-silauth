<?php

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
            'dsn' => null,
            'username' => null,
            'password' => null,
        ],
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'migrationNamespaces' => [
                'Sil\\SilAuth\\migrations\\',
            ],
            
            // Disable non-namespaced migrations.
            'migrationPath' => null,
        ],
    ],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
        ],
    ],
];
