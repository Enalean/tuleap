#!/bin/sh

set -e

substitute() {
  if [ -f $1 ]; then
    # $1: filename, $2: string to match, $3: replacement string
    perl -pi -e "s%$2%$3%g" $1
  fi
}

# Find where the script is
if echo "$0" | egrep -e '^/' 2>&1 >/dev/null; then
    bindir=$(dirname "$0")
else
    bindir=$(cd $(dirname "$0"); pwd)
fi

# Assume the script is in the "tools" subdirectory of the whole config
rootdir=$(cd $bindir/../../; pwd)

export CODENDI_LOCAL_INC=$rootdir/local.inc

#
# CLI
#
echo "Generate CLI"

$rootdir/src/utils/generate_cli_package.sh
