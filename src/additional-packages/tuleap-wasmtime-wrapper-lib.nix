{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; }
}:

let
  baseArchTarget = "x86_64";
  buildTargetRust = "${baseArchTarget}-unknown-linux-gnu";
  rust = pkgs.rust-bin.stable.latest.minimal.override {
    targets = [ "wasm32-wasip1" buildTargetRust ];
    extensions = [ "cargo" "rustc" "clippy" ];
  };
  tuleapWasmtimeWrapperLib = pkgs.stdenvNoCC.mkDerivation rec {
    name = "tuleap-wasmtime-wrapper-lib";
    src = ./wasmtime-wrapper-lib;

    cargoDeps = pkgs.rustPlatform.importCargoLock {
      lockFile = "${src}/Cargo.lock";
    };

    # Make zstd-sys crate to behave correctly in our "cross-compilation" setup
    CRATE_CC_NO_DEFAULTS = 1;

    nativeBuildInputs = [
      pkgs.rustPlatform.cargoSetupHook
      rust
      (import ./wasmtime-wrapper-lib/build-tools/cargo-zig-wrapper.nix { inherit pkgs; baseArch = baseArchTarget; })
    ];

    buildPhase = ''
      runHook preBuild
      HOME="$TMPDIR"
      cargo-build-glibc --release --frozen
      runHook postBuild
    '';

    installPhase = ''
      runHook preInstall
      mkdir $out/
      cp target/${buildTargetRust}/release/libwasmtimewrapper.so $out/
      cp libwasmtimewrapper.h $out/
      runHook postInstall
    '';

    doCheck = true;
    checkPhase = ''
      runHook preCheck
      cargo clippy
      cargo test --release
      runHook postCheck
    '';
  };
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-wasmtime-wrapper-lib-rpm-package";

  nativeBuildInputs = [ pkgs.rpm ];

  dontUnpack = true;
  dontConfigure = true;

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_sourcedir ${tuleapWasmtimeWrapperLib}" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      -bb ${./wasmtime-wrapper-lib/tuleap-wasmtime-wrapper-lib.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/${baseArchTarget}/*.rpm $out/
  '';

  dontFixUp = true;
}
