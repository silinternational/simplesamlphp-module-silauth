<?php

if ( ! class_exists('Yii')) {
    require_once __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
}

$yiiConfig = require(__DIR__ . '/config/yii2-config.php');
new yii\web\Application($yiiConfig); // Do NOT call run() here
