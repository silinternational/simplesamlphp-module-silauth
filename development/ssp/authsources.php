<?php

$config = [

    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ],


    // Use SilAuth
    'silauth' => [
        'silauth:SilAuth',
        'db.driver' => '',
        'db.host' => 'db',
        'db.database' => 'silauth',
        'db.username' => 'silauth',
        'db.password' => 'silauth',
        'db.charset' => 'utf8',
        'db.collation' => 'utf8_general_ci',
        'db.prefix' => '',
        'ldap.baseDn' => 'dc=acme,dc=org',
        'ldap.host' => 'ldap',
        'ldap.port' => '389',
        'ldap.useSsl' => false,
        'ldap.useTls' => true,
        'recaptcha.siteKey' => '',
        'recaptcha.secret' => '',
    ],

];
