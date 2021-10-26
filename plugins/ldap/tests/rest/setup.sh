#!/usr/bin/env bash

set -euxo pipefail

sudo -u codendiadm PHP="$PHP_CLI" /usr/share/tuleap/src/utils/tuleap plugin:install ldap
sudo -u codendiadm cp /usr/share/tuleap/plugins/ldap/tests/rest/ldap.inc /etc/tuleap/plugins/ldap/etc/ldap.inc