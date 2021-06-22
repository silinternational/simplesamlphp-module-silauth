#!/usr/bin/env bash

set -e
set -x

composer install --no-scripts

/data/symlink.sh

/data/run.sh
