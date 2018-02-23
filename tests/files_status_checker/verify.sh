#!/bin/bash
set +ex

name_of_verified_files="$1"
shift
verified_files="$(IFS='|'; echo -n "$*")" # No regex escaping, deal with it

if [[ -z "$name_of_verified_files" || -z "$verified_files" ]]; then
    >&2 echo "Usage: ./verify.sh <name_of_the_files_being_verified> files1 files2..."
    exit 1
fi

modified_files="$(git status --porcelain | grep -E "($verified_files)" || true)"
if [ -z "$modified_files" ]; then
    echo "All $name_of_verified_files are present and up to date!"
else
    echo "Your $name_of_verified_files does not seem to be present or up to date!"
    echo "$modified_files"
    exit 1
fi