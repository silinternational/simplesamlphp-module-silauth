#!/usr/bin/env bash

echo '************************************************'
echo 'This is not how tests are run. See the Makefile.'
echo '************************************************'
exit 1;

# Try to install composer dev dependencies
cd /data/vendor/simplesamlphp/simplesamlphp/modules/silauth
composer install --prefer-dist --no-interaction --optimize-autoloader --dev

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Try to run database migrations
./src/yii migrate --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Run the feature tests
./vendor/bin/behat

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Run the unit tests
cd src/tests
../../vendor/bin/phpunit .

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Switch back to the folder we were in.
cd -
