#!/usr/bin/env bash

set -euxo pipefail

MYSQL=$1
MYSQL_DBNAME=$2

# Avoid 3rd party service call (IHaveBeenPwned) during tests
$MYSQL $MYSQL_DBNAME -e "DELETE FROM password_configuration"
