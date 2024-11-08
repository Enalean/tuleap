{ pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { } }:

pkgs.mkShellNoCC {
  buildInputs = [
    pkgs.go
  ];
}
