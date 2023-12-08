{ pkgs }:

let
    node = pkgs.nodejs_20;
    nodePackages = (import ./pnpm { inherit pkgs; nodejs = node; });
    pnpm = nodePackages."pnpm-^8".override { dontNpmInstall = true; };
    nodeBaseBin = pkgs.stdenv.mkDerivation {
        name = "node-base-bin";
        unpackPhase = "true";
        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            ln -s ${node}/bin/node $out/bin/node
            runHook postInstall
        '';
    };
    pnpmCli = pkgs.stdenv.mkDerivation {
        name = "pnpm-cli";
        unpackPhase = "true";
        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            ln -s ${pnpm}/lib/node_modules/pnpm/bin/pnpm.cjs $out/bin/pnpm
            runHook postInstall
        '';
    };
    npmCliFallback = pkgs.writeShellScriptBin "npm" ''
        echo 'Please use pnpm. See adr/0007-js-package-manager.md for more information.'
        exit 1
    '';

in
pkgs.buildEnv {
    name = "build-tools-js";
    paths = [
        nodeBaseBin
        pnpmCli
        npmCliFallback
    ];
}
