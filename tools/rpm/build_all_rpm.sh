#!/usr/bin/env bash

set -ex

TULEAP_SOURCES="$1"
RPM_BUILD="$2"

push_nix_build_cache=''
if [ -n "${CACHIX_AUTH_TOKEN}" ]; then
    push_nix_build_cache='cachix watch-exec tuleap-community'
fi

build_tmp="$(mktemp -d)"

function cleanup {
    rm -rf "$build_tmp"
}
trap cleanup EXIT

mkdir -p "$RPM_BUILD/RPMS/noarch/"

export OS="${OS:-centos7}"

nix-build tools/rpm/tuleap-rpms.nix \
    --argstr tuleapRelease "${RELEASE}" \
    --argstr tuleapOS "${OS}" \
    --argstr withExperimental "${EXPERIMENTAL_BUILD:-0}" \
    --arg tuleapSourceTarballPath "$(readlink "$TULEAP_SOURCES"/result-tarball)" \
    --out-link "$build_tmp/packages"

cp -v -L "$build_tmp/packages/"*.rpm "$RPM_BUILD/RPMS/noarch/"

${push_nix_build_cache} "$TULEAP_SOURCES/"tools/rpm/build_nix_derivation.sh "$TULEAP_SOURCES/"tools/rpm/rpm-additional-packages.nix "$build_tmp/packages"
cp -v -L "$build_tmp/packages/"*.rpm "$RPM_BUILD/RPMS/noarch/"
if [ -d "$build_tmp/packages/${OS}" ]
then
    cp -v -L "$build_tmp/packages/${OS}"/*.rpm "$RPM_BUILD/RPMS/noarch/"
fi
chmod -v --recursive u+w "$RPM_BUILD/RPMS/noarch/"
