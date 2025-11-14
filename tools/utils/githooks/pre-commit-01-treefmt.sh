#!/usr/bin/env bash

files_changed="$(git diff --cached --name-only --diff-filter=ACMRTUXB)"

if [ -z "$files_changed" ]; then
    exit 0
fi

exec treefmt --fail-on-change --quiet -- $files_changed
