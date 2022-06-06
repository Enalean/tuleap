#!/usr/bin/env bash

set -euxo pipefail

install -o root -g root -m 0400 /usr/share/tuleap/plugins/create_test_env/etc/sudoers.d/tuleap_plugin_create_test_env /etc/sudoers.d/
echo 'a78e62ee64d594d99a800e5489c052d98cce84a54bb571bccc29b0dcd7ef4441' > /etc/tuleap/plugins/create_test_env/etc/creation_secret
chown codendiadm:codendiadm /etc/tuleap/plugins/create_test_env/etc/creation_secret
chmod 0400 /etc/tuleap/plugins/create_test_env/etc/creation_secret
