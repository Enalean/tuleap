#! /usr/bin/env bash

set -euo pipefail
set +x

current_dir="$(dirname "$(readlink -f "$0")")"

if [ -z "${INSIDE_NIX_SHELL_SIGN_REPOSITORIES:-}" ]; then
  export INSIDE_NIX_SHELL_SIGN_REPOSITORIES=1

  exec nix-shell -I nixpkgs="$current_dir"/../nix/pinned-nixpkgs.nix --packages curl jq uutils-coreutils-noprefix uutils-findutils --run "$0 $*"
fi

if [ -z "${1:-}" ]; then
    >&2 echo "A path with repositories to sign must be provided"
    exit 1
fi

if [ -z "${VAULT_SIGN_PATH_TO_KEY:-}" ]; then
    >&2 'VAULT_SIGN_PATH_TO_KEY environment variable must be set'
    exit 1
fi

. "$current_dir/vault.sh"

auth_token="$(get_vault_token)"

cleanup() {
    revoke_vault_token "$auth_token"
}
trap cleanup EXIT INT TERM

find "$1" -type f -name 'repomd.xml' -print0 | while IFS= read -r -d $'\0' repomdfile; do
    repomdfile_base64encoded="$(base64 --wrap 0 "$repomdfile")"
    echo "Signing $repomdfile"
    curl --silent --fail --header "X-Vault-Token: ${auth_token}" \
        --request POST \
        --data "{\"format\":\"ascii-armor\",\"input\":\"$repomdfile_base64encoded\"}" \
        "${VAULT_ADDR}/v1/${VAULT_SIGN_PATH_TO_KEY}" | \
        jq -r '.data.signature' > "$repomdfile.asc"
done
