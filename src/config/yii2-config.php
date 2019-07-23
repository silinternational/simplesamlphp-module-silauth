<?php

use Sil\JsonLog\target\JsonSyslogTarget;
use yii\helpers\Json;

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
        'log' => [
            'targets' => [
                [
                    'class' => JsonSyslogTarget::class,
                    'levels' => ['error', 'warning'],
                    'logVars' => [], // no need for default stuff: http://www.yiiframework.com/doc-2.0/yii-log-target.html#$logVars-detail
                    'prefix' => function ($message) {
                        $prefixData = [
                            'message' => $message,
                            'env' => YII_ENV,
                        ];

                        return Json::encode($prefixData);
                    },
                ],
            ],
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
