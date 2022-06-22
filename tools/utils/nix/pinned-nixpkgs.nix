{}:

let
  oxalicaRustOverlayJson = builtins.fromJSON (builtins.readFile ./oxalica-rust-overlay-pin.json);
  oxalicaRustOverlay = import (fetchTarball {
    url = "https://github.com/oxalica/rust-overlay/archive/${oxalicaRustOverlayJson.rev}.tar.gz";
    sha256 = oxalicaRustOverlayJson.sha256;
  });
  nixpkgsJson = builtins.fromJSON (builtins.readFile ./nixpkgs-pin.json);
  pinnedNixpkgs = import (fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/${nixpkgsJson.rev}.tar.gz";
    sha256 = nixpkgsJson.sha256;
  } ) { overlays = [ oxalicaRustOverlay ]; };
in pinnedNixpkgs
