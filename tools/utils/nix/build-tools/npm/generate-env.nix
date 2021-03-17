{ pkgs ? (import ../../pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
    buildInputs = [pkgs.bash pkgs.nodePackages.node2nix];
}
