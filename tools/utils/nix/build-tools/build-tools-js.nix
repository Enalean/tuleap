{ pkgs }:

let
  node = pkgs.nodejs_22;
  nodeBaseBin = pkgs.stdenvNoCC.mkDerivation {
    name = "node-base-bin";
    unpackPhase = "true";
    installPhase = ''
      runHook preInstall
      mkdir -p $out/bin/
      ln -s ${node}/bin/node $out/bin/node
      runHook postInstall
    '';
  };
  npmCliFallback = pkgs.writeShellScriptBin "npm" ''
    echo 'Please use pnpm. See docs/decisions/0007-js-package-manager.md for more information.'
    exit 1
  '';

in
pkgs.buildEnv {
  name = "build-tools-js";
  paths = [
    nodeBaseBin
    (pkgs.callPackage ./pnpm.nix { nodejs = nodeBaseBin; })
    npmCliFallback
  ];
}
