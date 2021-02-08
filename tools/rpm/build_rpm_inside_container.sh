#!/usr/bin/env bash

set -ex

TULEAP_SOURCES="/tuleap"
RPM_BUILD="/rpms"

mkdir -p /tmp
mkdir -p "$RPM_BUILD/"{BUILD,RPMS,SOURCES,SPECS,SRPMS}
cp "$TULEAP_SOURCES/"*tar.gz "$RPM_BUILD/SOURCES/"
OS='rhel7' make -C "$TULEAP_SOURCES/tools/rpm" rpm RPM_TMP="$RPM_BUILD"
