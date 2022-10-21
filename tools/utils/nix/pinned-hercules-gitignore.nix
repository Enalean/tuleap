{ pkgs ? (import <nixpkgs> {}) }:

let
  gitignoreJSONPin = builtins.fromJSON (builtins.readFile ./hercules-gitignore-nix-pin.json);
  gitignoreSrc = import (fetchTarball {
    url = "https://github.com/hercules-ci/gitignore.nix/archive/${gitignoreJSONPin.rev}.tar.gz";
    sha256 = gitignoreJSONPin.sha256;
  } ) { lib = pkgs.lib; };
in gitignoreSrc
