{ pkgs ? (import ./nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShellNoCC {
  buildInputs = [
    (import ./nix/default.nix { inherit pkgs; })
  ];
}
