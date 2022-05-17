#!/usr/bin/env bash
#
# Execute a git clone --mirror as gitolite user and set up the description.
# Used during project import into include/GitRepositoryManager.class.php
#  in create_from_bundle bundle.
#

GIT=/usr/bin/git
if [ -f /usr/lib/tuleap/git/bin/git ]; then
    GIT=/usr/lib/tuleap/git/bin/git
elif [ -f /opt/rh/rh-git218/root/usr/bin/git ]; then
    GIT=/opt/rh/rh-git218/root/usr/bin/git
fi

bundle_file_path="$1"
destination="$2"

umask 0007
mkdir -p "$destination"
cd "$destination"
$GIT init --bare
$GIT fetch "$bundle_file_path" '+refs/*:refs/*'
