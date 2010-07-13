#!/bin/sh

set -ex

#
# CI build: build Codendi RPMS on a Continuous Integration server
# Usage: sh ci_build.sh
# Note: WORKSPACE is a variable of Hudson
#

# Where application sources are checkouted
local_module_directory="codendi-src";

export DOCBOOK_TOOLS_DIR="$WORKSPACE/docbook"
export RPM_TMP="$WORKSPACE/RPM"

rm -rf "$RPM_TMP"

# Build official codendi rpms
pushd .
cd "$WORKSPACE/$local_module_directory/codendi_tools/rpm"
make all dist
popd
