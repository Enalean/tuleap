#!/usr/bin/env bash

set -euxo pipefail

sudo -u codendiadm PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap plugin:install fts_db
