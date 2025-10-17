{ }:

let
  treefmt-nix-pin = builtins.fromJSON (builtins.readFile ./treefmt-nix-pin.json);
in
import (fetchTarball {
  url = "https://github.com/numtide/treefmt-nix/archive/${treefmt-nix-pin.rev}.tar.gz";
  sha256 = treefmt-nix-pin.sha256;
})
