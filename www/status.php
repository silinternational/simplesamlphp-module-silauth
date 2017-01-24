<?php

use Sil\PhpEnv\Env;
use Sil\SilAuth\config\ConfigManager;
use Sil\SilAuth\system\System;

try {
    header('Content-Type: text/plain');
    
    ConfigManager::initializeYii2WebApp(['components' => ['db' => [
        'dsn' => sprintf(
            'mysql:host=%s;dbname=%s',
            Env::get('MYSQL_HOST'),
            Env::get('MYSQL_DATABASE')
        ),
        'username' => Env::get('MYSQL_USER'),
        'password' => Env::get('MYSQL_PASSWORD'),
    ]]]);
    $system = new System();
    $system->reportStatus();
    
} catch (\Throwable $e) {
    
    echo 'ERROR ' . $e->getCode() . ': ' . $e->getMessage(); // TEMP
    \http_response_code(500);
}
