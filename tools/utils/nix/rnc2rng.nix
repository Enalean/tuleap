{ pkgs ? (import ./pinned-nixpkgs.nix) {} }:

pkgs.mkShellNoCC {
  buildInputs = [
    pkgs.gnumake
    pkgs.findutils
    pkgs.bash
    pkgs.jing-trang
    pkgs.rnginline
  ];
}
