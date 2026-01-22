#!/usr/bin/env bash

set -ex

TULEAP_SOURCES="$1"
RPM_BUILD="$2"

build_tmp="$(mktemp -d)"

function cleanup {
    rm -rf "$build_tmp"
}
trap cleanup EXIT

mkdir -p "$RPM_BUILD/RPMS/noarch/"

nix-build "$TULEAP_SOURCES"/tools/rpm/tuleap-rpms.nix \
    --argstr tuleapRelease "${RELEASE}" \
    --argstr tuleapOS "${OS}" \
    --argstr withExperimental "${EXPERIMENTAL_BUILD:-0}" \
    --arg tuleapSourceTarballPath "$(readlink "$TULEAP_SOURCES"/result-tarball)" \
    --out-link "$build_tmp/packages"

cp -v -L "$build_tmp/packages/"*.rpm "$RPM_BUILD/RPMS/noarch/"

nix-build "$TULEAP_SOURCES/"tools/rpm/rpm-additional-packages.nix --out-link "$build_tmp/packages"

if [ -n "${PUSH_CACHE_NAME}" ]; then
    nix-store -qR --include-outputs $(nix-store -qd "$build_tmp/packages") \
        | grep -v '\.drv$' \
        | nix-shell -I nixpkgs=./tools/utils/nix/pinned-nixpkgs.nix -p attic-client --run "attic push --stdin ${PUSH_CACHE_NAME}"
fi

cp -v -L "$build_tmp/packages/"*.rpm "$RPM_BUILD/RPMS/noarch/"
if [ -d "$build_tmp/packages/${OS}" ]
then
    cp -v -L "$build_tmp/packages/${OS}"/*.rpm "$RPM_BUILD/RPMS/noarch/"
fi
chmod -v --recursive u+w "$RPM_BUILD/RPMS/noarch/"
