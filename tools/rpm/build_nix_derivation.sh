#!/usr/bin/env bash

set -ex

nix-build \
    --option extra-binary-caches 'https://tuleap-community.cachix.org' \
    --option extra-trusted-public-keys 'tuleap-community.cachix.org-1:c179Qc2Jd7DWWBkd/f8UWF7tx5u3Kapi+linIbU6Ozs=' \
    "$1" --out-link "$2"
