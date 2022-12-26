{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
  buildInputs = [
    pkgs.cargo-zigbuild
    pkgs.zig
    (pkgs.rust-bin.stable.latest.default.override {
      targets = [ "wasm32-wasi" "x86_64-unknown-linux-gnu" ];
      extensions = [ "cargo" "rustc" "rust-src" ];
    })
  ];
}
