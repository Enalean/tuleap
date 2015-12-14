#!/bin/bash
#
# Execute a git clone --mirror as gitolite user and set up the description.
# Used during project import into include/GitRepositoryManager.class.php
#  in create_from_bundle bundle.
#

bundle_file_path="$1"
destination="$2"
description="$3"

umask 0007
mkdir -p "$destination"
cd "$destination"
git init --bare
git fetch "$bundle_file_path" '+refs/*:refs/*'

echo "$description" > "description"
