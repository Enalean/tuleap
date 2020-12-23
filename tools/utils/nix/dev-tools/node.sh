#!/usr/bin/env sh

script=$(realpath "$0")
path=$(dirname "$script")

exec nix-shell --pure "$path/../../../../shell.nix" --run "node $*"
