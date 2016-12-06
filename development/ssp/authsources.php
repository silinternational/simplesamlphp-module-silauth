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
        'db.host' => '',
        'db.database' => '',
        'db.username' => '',
        'db.password' => '',
        'db.charset' => '',
        'db.collation' => '',
        'db.prefix' => '',
        'ldap.baseDn' => '',
        'ldap.host' => '',
        'ldap.port' => '',
        'ldap.useSsl' => '',
        'ldap.useTls' => '',
        'recaptcha.clientId' => '',
        'recaptcha.secret' => '',
    ],

];
