#!/bin/sh

set -ex

#
# CI build: build Codendi RPMS on a Continuous Integration server
# Usage: sh ci_build.sh
# Note: WORKSPACE is a variable of Hudson
#

# Where application sources are checkouted
local_module_directory="codendi-src";

codendi_src="$WORKSPACE/$local_module_directory"

export DOCBOOK_TOOLS_DIR="$WORKSPACE/docbook"
export RPM_TMP="$WORKSPACE/RPM"

rm -rf "$RPM_TMP"

# If ci-build was launched, clean up sources
if [ -L "$codendi_src/plugins/tests" ]; then
    rm -f "$codendi_src/plugins/tests"
fi

# Build official codendi rpms
pushd .
cd "$codendi_src/tools/rpm"
make all dist
popd

exit 0
