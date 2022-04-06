#!/usr/bin/env bash

set -ex

nix-build "$1" --out-link "$2"
