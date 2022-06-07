#!/usr/bin/env bash

set -euxo pipefail

# Needed until Docker images used for tests are rebuilt with the deployed sudoers file
install -o root -g root -m 0440 /usr/share/tuleap/plugins/git/etc/sudoers.d/git-create-new-branch /etc/sudoers.d/
