#!/usr/bin/env bash

# Try to install composer dev dependencies
cd /data/vendor/simplesamlphp/simplesamlphp/modules/silauth
composer install --prefer-dist --no-interaction --optimize-autoloader --dev

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

whenavail ldap 389 30 sleep 10

# Try to run database migrations
./vendor/bin/yii migrate --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# run unit tests
./vendor/bin/phpunit tests/
