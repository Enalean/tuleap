#! /usr/bin/env nix-shell
#! nix-shell ./generate-env.nix -i bash

cd "$(dirname $0)"
node2nix -i ./node-packages.json
