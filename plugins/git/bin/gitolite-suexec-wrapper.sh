#!/bin/bash
#
# Suexec wrapper for gitolite-shell
#

export GIT_PROJECT_ROOT="/var/lib/codendi/gitolite/repositories"
export GITOLITE_HTTP_HOME="/usr/com/gitolite"

exec sudo -E -u gitolite /usr/bin/gl-auth-command
