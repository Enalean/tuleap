{ pkgs ? (import ../pinned-nixpkgs.nix) {} }:

pkgs.mkShellNoCC {
    buildInputs = [pkgs.bash pkgs.nodePackages.node2nix];
}
