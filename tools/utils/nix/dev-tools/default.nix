{ pkgs ? (import ../pinned-nixpkgs.nix) {}, phpBase ? (import ../php-base.nix { inherit pkgs; }) }:

let
  devToolsPhpBase = import ./dev-tools-php.nix { inherit pkgs; };
  buildTools = import ../build-tools { inherit pkgs; phpBase = devToolsPhpBase; };
in pkgs.mkShellNoCC {
  name = "dev-tools";
  buildInputs = buildTools.buildInputs ++ [
    pkgs.osv-scanner
    (import ./dev-tools-docker.nix { inherit pkgs; })
    (import ./dev-tools-old-browsers.nix { inherit pkgs; })
    (import ./dev-tools-tests.nix { inherit pkgs; })
  ];
}
