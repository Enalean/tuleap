#! /usr/bin/env bash

set -euo pipefail
set +x

vault_addr="$1"
vault_token="$2"
key_path="$3"
signature_file="$4"
file_to_sign="$5"
file_payload_to_sign="$(mktemp)"
cleanup() {
    rm "$file_payload_to_sign"
}
trap cleanup EXIT INT TERM

echo "{\"input\":\"$(base64 --wrap 0 "$file_to_sign")\"}" > "$file_payload_to_sign"
curl --silent --fail --header "X-Vault-Token: ${vault_token}" \
    --request POST \
    --data-binary "@$file_payload_to_sign" \
    "$vault_addr/v1/$key_path" | \
    jq -r '.data.signature' | \
    base64 -d > "$signature_file"
