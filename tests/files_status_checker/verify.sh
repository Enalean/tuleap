#!/usr/bin/env bash
set +ex

repository_to_inspect="$1"
name_of_verified_files="$2"
shift
verified_files="$(IFS='|'; echo -n "$*")" # No regex escaping, deal with it

if [[ -z "$name_of_verified_files" || -z "$verified_files" || -z "$repository_to_inspect" ]]; then
    >&2 echo "Usage: ./verify.sh <repository_to_inspect> <name_of_the_files_being_verified> files1 files2..."
    exit 1
fi

modified_files="$(git --git-dir="$repository_to_inspect"/.git/ --work-tree="$repository_to_inspect" status --porcelain | grep -E "($verified_files)" || true)"
if [ -z "$modified_files" ]; then
    echo "All $name_of_verified_files are present and up to date!"
else
    echo "Your $name_of_verified_files does not seem to be present or up to date!"
    echo "$modified_files"
    exit 1
fi
