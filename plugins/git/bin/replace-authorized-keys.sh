#!/usr/bin/env bash

set -e

if [ -z "$1" ]; then
    (>&2 echo "Usage: $0 path_to_existing_authorized_keys_file")
    exit 1
fi

chmod 0600 "$1"
chown gitolite:gitolite "$1"

su -c "mkdir -p /var/lib/gitolite/.ssh/" -l gitolite
mv "$1" /var/lib/gitolite/.ssh/authorized_keys