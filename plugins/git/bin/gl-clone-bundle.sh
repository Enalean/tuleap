#!/usr/bin/env bash
#
# Execute a git clone --mirror as gitolite user and set up the description.
# Used during project import into include/GitRepositoryManager.class.php
#  in create_from_bundle bundle.
#

GIT=/usr/lib/tuleap/git/bin/git
if [ ! -f $GIT ]; then
    echo "*** ERROR: $GIT is missing"
    exit 1
fi

bundle_file_path="$1"
destination="$2"

umask 0007
mkdir -p "$destination"
cd "$destination"
$GIT init --bare
$GIT fetch "$bundle_file_path" '+refs/*:refs/*'
