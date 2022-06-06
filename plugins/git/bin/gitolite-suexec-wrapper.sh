#!/usr/bin/env bash
#
# Suexec wrapper for gitolite-shell
#

export GIT_PROJECT_ROOT="/var/lib/codendi/gitolite/repositories"
if [ -d "/usr/com/gitolite" ]; then
    export GITOLITE_HTTP_HOME="/usr/com/gitolite"
elif [ -d "/var/lib/gitolite" ]; then
    export GITOLITE_HTTP_HOME="/var/lib/gitolite"
else
    echo "No valid gitolite home found"
    exit 1
fi

exec sudo -E -u gitolite /usr/bin/gl-auth-command
