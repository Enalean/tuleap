#! /usr/bin/env nix-shell
#! nix-shell -p nix-prefetch-git -i bash

nix-prefetch-git https://github.com/NixOS/nixpkgs.git refs/heads/nixpkgs-unstable > "$(dirname $0)"/nixpkgs-pin.json
nix-prefetch-git https://github.com/oxalica/rust-overlay.git refs/heads/master > "$(dirname $0)"/oxalica-rust-overlay-pin.json
