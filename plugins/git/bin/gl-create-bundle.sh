#!/usr/bin/env bash
#
# Execute a git create bundle as gitolite user.
# Used during project export.
#

GIT=/usr/bin/git
if [ -f /usr/lib/tuleap/git/bin/git ]; then
    GIT=/usr/lib/tuleap/git/bin/git
elif [ -f /opt/rh/rh-git218/root/usr/bin/git ]; then
    GIT=/opt/rh/rh-git218/root/usr/bin/git
fi

repository_path="$1"
file_name="$2"

umask 77
$GIT --git-dir=$repository_path bundle create "$repository_path/$file_name" --all
