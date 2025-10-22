{ }:

let
  oxalicaRustOverlayJson = builtins.fromJSON (builtins.readFile ./oxalica-rust-overlay-pin.json);
  oxalicaRustOverlay = import (fetchTarball {
    url = "https://github.com/oxalica/rust-overlay/archive/${oxalicaRustOverlayJson.rev}.tar.gz";
    sha256 = oxalicaRustOverlayJson.sha256;
  });
  # Needed until we bump our pin after the merge commit of https://github.com/NixOS/nixpkgs/pull/454155
  overlayCreateRepoFix = (
    final: prev: {
      createrepo_c = prev.createrepo_c.overrideAttrs (old: {
        patches = (old.patches or [ ]) ++ [
          (prev.fetchpatch {
            name = "do-not-require-doxygen.patch";
            url = "https://github.com/rpm-software-management/createrepo_c/commit/908e3a4a5909ab107da41c2631a06c6b23617f3c.patch";
            hash = "sha256-0M1WKj5ez78oiXH334j7lKFKgfCpo5Uh1hqdtLlGu3g=";
          })
        ];
        postPatch = old.postPatch + ''
          substituteInPlace CMakeLists.txt \
                --replace-fail "CMAKE_MINIMUM_REQUIRED (VERSION 2.8.12)" "cmake_minimum_required(VERSION 3.10)"
        '';
      });
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
          overlayCreateRepoFix
        ];
      };
in
pinnedNixpkgs
