#!/usr/bin/env bash
#
# Suexec wrapper for gitolite-shell
#

export GIT_PROJECT_ROOT="/var/lib/codendi/gitolite/repositories"
export GITOLITE_HTTP_HOME="/var/lib/gitolite"
export TERM=linux

exec sudo -E -u gitolite /usr/share/gitolite3/gitolite-shell
