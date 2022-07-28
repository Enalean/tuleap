{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
  buildInputs = [
    pkgs.go
  ];
}
