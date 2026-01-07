{
  pkgs ? (import ../pinned-nixpkgs.nix) { },
}:

let
  devToolsPhpBase = import ./dev-tools-php.nix { inherit pkgs; };
  buildToolsShell = import ../build-tools {
    inherit pkgs;
    phpBase = devToolsPhpBase;
  };
in
buildToolsShell.overrideAttrs {
  name = "dev-tools";
  buildInputs = buildToolsShell.buildInputs ++ [
    pkgs.osv-scanner
    pkgs.gcc # Used by OSV Scanner to perform call analysis
    pkgs.cdxgen
    pkgs.go_latest
    pkgs.cyclonedx-gomod
    (import ./dev-tools-docker.nix { inherit pkgs; })
    (import ./dev-tools-old-browsers.nix { inherit pkgs; })
    (import ./dev-tools-tests.nix { inherit pkgs; })
  ];
}
