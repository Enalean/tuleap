#!/usr/bin/env bash

set -euxo pipefail

FINAL_CONTENT_DIR=./result-additional-tools

cp -v -r -L \
    "$(nix-build --no-out-link "$(dirname "$(readlink -f "$0")")/additional-tools.nix")" \
    "$FINAL_CONTENT_DIR"

chmod -R +w "$FINAL_CONTENT_DIR"

export VAULT_ADDR="https://vault.enalean.com"

find "$FINAL_CONTENT_DIR" -type f -exec cosign sign-blob --yes --key=hashivault://tuleap-additional-tools-signing --bundle="{}.bundle" {} \;
