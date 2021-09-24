#! /usr/bin/env nix-shell
#! nix-shell ./generate-env.nix -i bash

node2nix -i ./node-packages.json
