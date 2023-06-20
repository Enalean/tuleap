{ pkgs ? (import ../nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
  buildInputs = [
    (pkgs.rust-bin.stable.latest.default.override {
      targets = [ "wasm32-wasi" ];
      extensions = [ "cargo" "rustc" "rust-src" ];
    })
  ];
}
