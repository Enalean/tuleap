{}:

let
  nixpkgsJson = builtins.fromJSON (builtins.readFile ./nixpkgs-pin.json);
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${nixpkgsJson.rev}.tar.gz";
    sha256 = nixpkgsJson.sha256;
  } ) { };
in pinnedNixpkgs
