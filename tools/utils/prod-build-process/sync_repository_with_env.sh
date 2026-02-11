#! /usr/bin/env bash

set -euxo pipefail

current_dir="$(dirname "$(readlink -f "$0")")"

exec nix-shell -I nixpkgs="$TULEAP_SOURCES"/tools/utils/nix/pinned-nixpkgs.nix \
    -p rsync -p openssh -p curl -p jq \
    --run "$current_dir/sync_repository.sh"
