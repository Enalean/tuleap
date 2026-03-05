#! /usr/bin/env bash

set -euo pipefail
set +x

current_dir="$(dirname "$(readlink -f "$0")")"

if [ -z "${INSIDE_NIX_SHELL_SIGN_PACKAGES:-}" ]; then
  export INSIDE_NIX_SHELL_SIGN_PACKAGES=1

  exec nix-shell -I nixpkgs="$current_dir"/../nix/pinned-nixpkgs.nix --packages curl jq uutils-coreutils-noprefix uutils-findutils rpm --run "$0 $*"
fi

if [ -z "${1:-}" ]; then
    >&2 echo "A path with packages to sign must be provided"
    exit 1
fi

if [ -z "${VAULT_SIGN_PATH_TO_KEY:-}" ]; then
    >&2 'VAULT_SIGN_PATH_TO_KEY environment variable must be set'
    exit 1
fi

. "$current_dir/vault.sh"

build_tmp="$(mktemp -d)"

auth_token="$(get_vault_token)"

cleanup() {
    revoke_vault_token "$auth_token"
    rm -rf "$build_tmp"
}
trap cleanup EXIT INT TERM

export GPG_TTY=$(tty)

find "$1" -type f -name '*.rpm' -print0 | while IFS= read -r -d '' file; do
    echo "GPG signing $file"
    sha256_file="$(sha256sum "$file" | head -c 64)"
    result_signing="$(rpm \
        --define "_tmppath '$build_tmp'" \
        --define "_gpg_name $VAULT_SIGN_PATH_TO_KEY" \
        --define "__gpg_sign_cmd $current_dir/gpg_lookalike_vault_rpm.sh %{_} '$VAULT_ADDR' '$auth_token' '%{_gpg_name}' '%{__signature_filename}' '%{__plaintext_filename}'" \
        --addsign "$file" 2>&1)"
    echo "$result_signing" | grep -q 'contains identical signature, skipping' && \
        echo "$file was already signed, skipping" && continue
    echo "$result_signing"
    if [ "$sha256_file" == "$(sha256sum "$file" | head -c 64)" ]; then
        echo "Package $file does not seem to have been signed"
        exit 1
    fi
done
