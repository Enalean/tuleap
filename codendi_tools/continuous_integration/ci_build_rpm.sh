#!/bin/sh

set -ex

#
# CI build: build Codendi project on a Continuous Integration server
# Usage: sh ci_build.sh <sys_default_domain> <sys_ip_address>
# Note: WORKSPACE is a variable of Hudson
#

build_type="short"
if [ "$1" = "full" ]; then
    build_type="full"
fi

local_module_directory="codendi-src";

#################
# RPM           #
#################

export DOCBOOK_TOOLS_DIR="$WORKSPACE/docbook"
export RPM_TMP="$WORKSPACE/RPM"

rm -rf "$RPM_TMP"

if [ "$build_type" = "short" ]; then
    # Build official codendi rpms
    pushd .
    cd "$WORKSPACE/$local_module_directory/codendi_tools/rpm"
    make all dist
    popd
else
    pushd .
    cd "$WORKSPACE/rpm_customization"
    export CODENDI_SRC="$WORKSPACE/$local_module_directory"
    export FORGEUPGRADE_SRC="$WORKSPACE"
    make dist
    popd
fi

rm -f /tmp/docbook-cug-*
rm -f /tmp/log_xml2html_*
rm -f /tmp/log_xml2pdf_*

# Publish repository
#pushd .
#cd "$WORKSPACE/$local_module_directory/codendi_tools/rpm"
#make dist
#popd
