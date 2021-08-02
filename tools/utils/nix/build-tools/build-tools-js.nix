{ pkgs, php }:

let
    node = pkgs.nodejs-16_x;
    nodePackages = (import ./npm { inherit pkgs; nodejs = node; });
    npm = nodePackages."npm-^7".override { dontNpmInstall = true; };
    nodeBaseBin = pkgs.stdenv.mkDerivation {
        name = "node-base-bin";
        unpackPhase = "true";
        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            ln -s ${node}/bin/node $out/bin/node
            ln -s ${npm}/lib/node_modules/npm/bin/npx-cli.js $out/bin/npx
            runHook postInstall
        '';
    };
    npmCli = pkgs.stdenv.mkDerivation {
        name = "npm-cli";
        unpackPhase = "true";
        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            ln -s ${npm}/lib/node_modules/npm/bin/npm-cli.js $out/bin/npm
            runHook postInstall
        '';
    };
    npmCliCleanTuleapLocalDep = pkgs.resholvePackage {
        pname = "npm-clean-tuleap-local-dep";
        version = "unreleased";
        src = [ ./npm-cli-clean-up-local-tuleap-dep.sh ./clean-lockfile-from-local-tuleap-dep.php ];

        unpackPhase = ''
            runHook preUnpack
            for srcFile in $src; do
              cp $srcFile $(stripHash $srcFile)
            done
            runHook postUnpack
        '';

        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            install -Dv *.sh *.php $out/
            ln -s $out/npm-cli-clean-up-local-tuleap-dep.sh $out/bin/npm
            runHook postInstall
        '';

        solutions = {
            default = {
              scripts = [ "npm-cli-clean-up-local-tuleap-dep.sh" ];
              interpreter = "${pkgs.bash}/bin/bash";
              inputs = (php) ++ [ npmCli pkgs.coreutils ];
            };
        };
    };
in
pkgs.buildEnv {
    name = "build-tools-js";
    paths = [
        nodeBaseBin
        npmCliCleanTuleapLocalDep
    ];
}
