<?php

use Sil\PhpEnv\Env;

return [
    'silauth:SilAuth',
    'auth.trustedIpAddresses' => Env::get('TRUSTED_IP_ADDRESSES'),
    'idBroker.accessToken' => Env::get('ID_BROKER_ACCESS_TOKEN'),
    'idBroker.baseUri' => Env::get('ID_BROKER_BASE_URI'),
    'idBroker.idpDomainName' => Env::requireEnv('IDP_DOMAIN_NAME'),
    'link.forgotPassword' => Env::get('FORGOT_PASSWORD_URL'),
    'mysql.host' => Env::get('MYSQL_HOST'),
    'mysql.database' => Env::get('MYSQL_DATABASE'),
    'mysql.user' => Env::get('MYSQL_USER'),
    'mysql.password' => Env::get('MYSQL_PASSWORD'),
    'recaptcha.siteKey' => Env::get('RECAPTCHA_SITE_KEY'),
    'recaptcha.secret' => Env::get('RECAPTCHA_SECRET'),
];
