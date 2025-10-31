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
  # Overlay to move to Go 1.25.3, to be removed after the next pin upgrade
  nixpkgs2505 =
    (import (fetchTarball {
      url = "https://github.com/NixOS/nixpkgs/archive/daf6dc47aa4b44791372d6139ab7b25269184d55.tar.gz";
      sha256 = "0ddhdypgkg4cs5zy7y5wjl62y8nrfx7xh6b95l4rkbpnl2xzn5f3";
    }))
      { };
  overlayGo1253 = (
    final: prev: {
      go_latest = nixpkgs2505.go_1_25;
      buildGoLatestModule = nixpkgs2505.buildGo125Module;
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
          overlayGo1253
        ];
      };
in
pinnedNixpkgs
