#! /usr/bin/env bash

set -euxo pipefail

current_dir="$(dirname "$(readlink -f "$0")")"

if [ -z "${INSIDE_NIX_SHELL_AV_SCAN:-}" ]; then
  export INSIDE_NIX_SHELL_AV_SCAN=1

  exec nix-shell -I nixpkgs="$current_dir"/../nix/pinned-nixpkgs.nix --packages clamav --run "$0 $*"
fi

if [ -z "${1:-}" ]; then
    >&2 echo "A path to scan must be provided"
    exit 1
fi

av_database_dir="$(mktemp -d)"

function cleanup {
    rm -rf "$av_database_dir"
}
trap cleanup EXIT

freshclam --datadir="$av_database_dir" --config-file="$current_dir"/freshclam.conf

clamscan --database="$av_database_dir" --disable-cache --recursive "$1"
