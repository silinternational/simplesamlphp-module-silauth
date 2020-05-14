#!/usr/bin/env bash

./setup-logentries.sh

# Try to install composer dev dependencies
cd /data/vendor/simplesamlphp/simplesamlphp/modules/silauth
COMPOSER_ROOT_VERSION=dev-develop composer install --no-interaction --optimize-autoloader --no-scripts --no-progress

# If that failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Try to run database migrations
./src/yii migrate --interactive=0

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Run the feature tests
./vendor/bin/behat --config=features/behat.yml

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Switch back to the folder we were in.
cd -

# Run the unit tests
cd /data/vendor/simplesamlphp/simplesamlphp/modules/silauth/src/tests
../../vendor/bin/phpunit .

# If they failed, exit.
rc=$?; if [[ $rc != 0 ]]; then exit $rc; fi

# Switch back to the folder we were in.
cd -
