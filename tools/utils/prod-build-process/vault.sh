#! /usr/bin/env bash

set -euo pipefail
set +x

if [ -z "${VAULT_ADDR:-}" ]; then
    >&2 'VAULT_ADDR environment variable must be set'
    exit 1
fi

if [ -z "${VAULT_ROLE_ID:-}" ]; then
    >&2 'VAULT_ROLE_ID environment variable must be set'
    exit 1
fi

if [ -z "${VAULT_SECRET_ID:-}" ]; then
    >&2 'VAULT_SECRET_ID environment variable must be set'
    exit 1
fi

get_vault_token() {
    if [ -z "${VAULT_TOKEN:-}" ]; then
        curl --silent --fail --request POST \
            -d "{\"role_id\":\"$VAULT_ROLE_ID\",\"secret_id\":\"$VAULT_SECRET_ID\"}" \
            "${VAULT_ADDR}/v1/auth/approle/login" | \
            jq -r '.auth.client_token'
    else
        echo "${VAULT_TOKEN}"
    fi
}

revoke_vault_token() {
    readonly token="$1"

    curl --silent --fail --header "X-Vault-Token: $token" \
        --request POST "${VAULT_ADDR}/v1/auth/token/revoke-self"
}
