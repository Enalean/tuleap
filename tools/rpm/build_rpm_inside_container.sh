#!/usr/bin/env bash

set -ex

TULEAP_SOURCES="/tuleap"
RPM_BUILD="/rpms"

push_nix_build_cache=''
if [ -s "/cachix_auth_token" ]; then
    push_nix_build_cache='cachix watch-exec tuleap-community'
    cachix authtoken --stdin < /cachix_auth_token
fi

mkdir -p /tmp
mkdir -p "$RPM_BUILD/"{BUILD,RPMS,SOURCES,SPECS,SRPMS}
cp "$TULEAP_SOURCES/"*tar.gz "$RPM_BUILD/SOURCES/"
OS='rhel7' make -C "$TULEAP_SOURCES/tools/rpm" rpm RPM_TMP="$RPM_BUILD"
${push_nix_build_cache} "$TULEAP_SOURCES/"tools/rpm/build_nix_derivation.sh "$TULEAP_SOURCES/"tools/rpm/rpm-additional-packages.nix /tmp/result
cp -v -r -L /tmp/result/. "$RPM_BUILD/RPMS/noarch/"
chmod -v --recursive u+w "$RPM_BUILD/RPMS/noarch/"