#!/usr/bin/env bash

ln -s /silauth /data/vendor/simplesamlphp/simplesamlphp/modules/silauth

ln -s /silauth/development/ssp/authsources.php /data/vendor/simplesamlphp/simplesamlphp/config/authsources.php
ln -s /silauth/development/ssp/saml20-idp-hosted.php /data/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-hosted.php
ln -s /silauth/development/ssp/saml.crt /data/vendor/simplesamlphp/simplesamlphp/cert/saml.crt
ln -s /silauth/development/ssp/saml.pem /data/vendor/simplesamlphp/simplesamlphp/cert/saml.pem

/data/run.sh
