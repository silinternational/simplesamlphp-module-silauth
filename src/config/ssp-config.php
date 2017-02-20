<?php

use Sil\PhpEnv\Env;

return [
    'silauth:SilAuth',
    'link.forgotPassword' => Env::get('FORGOT_PASSWORD_URL'),
    'mysql.host' => Env::get('MYSQL_HOST'),
    'mysql.database' => Env::get('MYSQL_DATABASE'),
    'mysql.user' => Env::get('MYSQL_USER'),
    'mysql.password' => Env::get('MYSQL_PASSWORD'),
    'recaptcha.siteKey' => Env::get('RECAPTCHA_SITE_KEY'),
    'recaptcha.secret' => Env::get('RECAPTCHA_SECRET'),
];
