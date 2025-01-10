{ pkgs ? (import ../nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
  buildInputs = [
    pkgs.go
  ];
}
