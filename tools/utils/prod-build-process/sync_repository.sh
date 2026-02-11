#! /usr/bin/env bash

set -euxo pipefail

if [ -z "${INSIDE_NIX_SHELL_SYNC_REPO:-}" ]; then
  export INSIDE_NIX_SHELL_SYNC_REPO=1

  current_dir="$(dirname "$(readlink -f "$0")")"
  exec nix-shell -I nixpkgs="$current_dir"/../nix/pinned-nixpkgs.nix --packages rsync openssh curl jq --run "$0 $*"
fi

build_tmp="$(mktemp -d)"

set +x

auth_token="$(curl --silent --fail -X POST -d "{\"role_id\":\"$ROLE_ID\",\"secret_id\":\"$SECRET_ID\"}" ${VAULT_ADDR}/v1/auth/approle/login | jq -r '.auth.client_token')"

function cleanup {
    rm -rf "$build_tmp"
    set +x
    curl --silent --fail \
        --header "X-Vault-Token: $auth_token" \
        --request POST "$VAULT_ADDR/v1/auth/token/revoke-self"
}
trap cleanup EXIT

touch "$build_tmp"/key.json
chmod 0600 "$build_tmp"/key.json
touch "$build_tmp"/key
chmod 0600 "$build_tmp"/key

curl --silent --fail \
    --header "X-Vault-Token: $auth_token" \
    -X POST -d "{\"key_type\":\"ed25519\"}" \
    "$VAULT_ADDR/v1/ssh/issue/tuleap-rpm-${TULEAP_FLAVOR}" \
    -o "$build_tmp"/key.json
jq -r .data.private_key < "$build_tmp"/key.json > "$build_tmp"/key
jq -r .data.signed_key < "$build_tmp"/key.json > "$build_tmp"/key.cert

set -x

rsync -av --mkpath --delete-after \
    -e "ssh -i $build_tmp/key.cert -i $build_tmp/key -p2222 " \
    "${SOURCE_TO_SYNC}" "tuleap-rpm-${TULEAP_FLAVOR}@${SYNC_TARGET}"
