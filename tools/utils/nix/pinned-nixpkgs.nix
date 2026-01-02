{ }:

let
  oxalicaRustOverlayJson = builtins.fromJSON (builtins.readFile ./oxalica-rust-overlay-pin.json);
  oxalicaRustOverlay = import (fetchTarball {
    url = "https://github.com/oxalica/rust-overlay/archive/${oxalicaRustOverlayJson.rev}.tar.gz";
    sha256 = oxalicaRustOverlayJson.sha256;
  });
  # Overlay to keep editorconfig-checker 3.4.1 due to https://github.com/editorconfig-checker/editorconfig-checker/issues/515
  nixpkgsEditorConfigChecker341 =
    (import (fetchTarball {
      url = "https://github.com/NixOS/nixpkgs/archive/cb82756ecc37fa623f8cf3e88854f9bf7f64af93.tar.gz";
      sha256 = "1a28dlvrh2y1mps04f0mzb56syhkjd60zvr60brirvsgbrmcx46h";
    }))
      { };
  overlayEditorConfigChecker = (
    final: prev: {
      editorconfig-checker = nixpkgsEditorConfigChecker341.editorconfig-checker;
    }
  );
  nixpkgsJson = builtins.fromJSON (builtins.readFile ./nixpkgs-pin.json);
  pinnedNixpkgs =
    import
      (fetchTarball {
        url = "https://github.com/NixOS/nixpkgs/archive/${nixpkgsJson.rev}.tar.gz";
        sha256 = nixpkgsJson.sha256;
      })
      {
        overlays = [
          oxalicaRustOverlay
          overlayEditorConfigChecker
        ];
      };
in
pinnedNixpkgs
