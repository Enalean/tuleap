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
# User Guide
#
echo "Generate User Guide"
user_guide="$rootdir/documentation/user_guide"

make -C $user_guide

#
# Programmer guide
#
echo "Generate Programmer Guide"
programmer_guide="$rootdir/documentation/programmer_guide"

make -C $programmer_guide

#
# CLI
#
echo "Generate CLI"

$rootdir/src/utils/generate_cli_package.sh
