<?php

use Sil\PhpEnv\Env;

return [
    'silauth:SilAuth',
    'mysql.host' => Env::get('MYSQL_HOST'),
    'mysql.database' => Env::get('MYSQL_DATABASE'),
    'mysql.user' => Env::get('MYSQL_USER'),
    'mysql.password' => Env::get('MYSQL_PASSWORD'),
    'ldap.acct_suffix' => Env::get('LDAP_ACCT_SUFFIX'),
    'ldap.domain_controllers' => explode('|', Env::get('LDAP_DOMAIN_CONTROLLERS')),
    'ldap.base_dn' => Env::get('LDAP_BASE_DN'),
    'ldap.admin_username' => Env::get('LDAP_ADMIN_USERNAME'),
    'ldap.admin_password' => Env::get('LDAP_ADMIN_PASSWORD'),
    'ldap.use_ssl' => Env::get('LDAP_USE_SSL', true),
    'ldap.use_tls' => Env::get('LDAP_USE_TLS', true),
    'ldap.timeout' => Env::get('LDAP_TIMEOUT', 5),
    'recaptcha.siteKey' => '',
    'recaptcha.secret' => '',
];
