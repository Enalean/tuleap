#!/usr/bin/env bash
#
# Import SVN repository from a dumpfile

set -e

svnadmin="svnadmin"

if svnadmin --version | head -1 | grep -v --quiet --no-messages 'version 1.6'; then
    svnadmin="svnadmin --bypass-prop-validation"
fi

repository_path="$1"
dumpfile_path="$2"

if [ ! -d "$repository_path" ]; then
    echo "Repository: $repository_path doesn't exist" >&2
    exit 1
fi

if [ ! -f "$dumpfile_path" ]; then
    echo "Dumpfile: $dumpfile_path doesn't exist or not readable" >&2
    exit 1
fi

umask 0027

set -x

exec $svnadmin load $repository_path < $dumpfile_path 2>&1
