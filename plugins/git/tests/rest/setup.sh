#!/usr/bin/env bash

set -euxo pipefail

# Needed until Docker images used for tests are rebuilt with the deployed sudoers file
install -o root -g root -m 0400 /usr/share/tuleap/plugins/git/etc/sudoers.d/git-change-default-branch /etc/sudoers.d/
