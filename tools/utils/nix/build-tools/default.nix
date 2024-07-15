{ pkgs ? (import ../pinned-nixpkgs.nix) {}, phpBase ? (import ../php-base.nix { inherit pkgs; }) }:

let
  php = import ./build-tools-php.nix { inherit pkgs phpBase; };
in pkgs.mkShellNoCC {
  buildInputs = (import ./build-tools-general.nix { inherit pkgs; }) ++
    (import ./build-tools-packages.nix { inherit pkgs; }) ++
    (php) ++
    [(import ./build-tools-js.nix { inherit pkgs; })];
}
