{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:


pkgs.mkShellNoCC {
  buildInputs = [
    (import ./build-tools/cargo-zig-wrapper.nix { inherit pkgs; })
    (pkgs.rust-bin.stable.latest.default.override {
      targets = [ "wasm32-wasip1" "x86_64-unknown-linux-gnu" ];
      extensions = [ "cargo" "rustc" "rust-src" "clippy" ];
    })
  ];
  CARGO_TARGET_X86_64_UNKNOWN_LINUX_GNU_LINKER="zigcc";
  CARGO_UNSTABLE_TARGET_APPLIES_TO_HOST="true";
  CARGO_TARGET_APPLIES_TO_HOST="false";
  __CARGO_TEST_CHANNEL_OVERRIDE_DO_NOT_USE_THIS="nightly";
}
