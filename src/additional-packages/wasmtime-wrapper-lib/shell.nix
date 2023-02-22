{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
  buildInputs = [
    pkgs.rust-bin.stable.latest.default
    pkgs.cargo-zigbuild
    pkgs.zig
  ];
}
