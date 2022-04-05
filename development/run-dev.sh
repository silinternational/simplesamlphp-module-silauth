#!/usr/bin/env bash

set -e
set -x

# Since composer tends to delete files, ensure we keep a copy of the config.php
cp /data/vendor/simplesamlphp/simplesamlphp/config/config.php /data/ssp-config.php

composer install --no-scripts

cp /data/ssp-config.php /data/vendor/simplesamlphp/simplesamlphp/config/config.php

/data/symlink.sh

/data/run.sh
