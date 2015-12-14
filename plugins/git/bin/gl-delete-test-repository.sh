#!/bin/bash
#
# Allows to delete the repositories created during tests
#
if ! cd "$1"; then
    exit 1
fi

repo_path="$PWD"

if [[ "$repo_path" == /tmp/tuleap_tests_*/gitolite/repositories/* ]]
then
    exec rm -fr "$repo_path"
else
    echo "$repo_path is not a valid path to remove."
    exit 1
fi
