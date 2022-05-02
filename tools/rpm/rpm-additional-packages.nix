{ pkgs ? (import ../utils/nix/pinned-nixpkgs.nix) {} }:

let
  filterAdditionalPackagesNixFiles = paths:
    builtins.filter (path: builtins.match ".*/additional-packages/[^/]+\.nix" (builtins.toString path) != null) paths;
  importPath = path: import path { inherit pkgs; };
in pkgs.symlinkJoin {
  name = "rpm-additional-packages";
  paths = pkgs.lib.pipe ../../. [
    pkgs.lib.filesystem.listFilesRecursive
    filterAdditionalPackagesNixFiles
    (builtins.map importPath)
  ];
}
