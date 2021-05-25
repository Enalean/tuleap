{ pkgs }:

let
    node = pkgs.nodejs-16_x;
    nodePackages = (import ./npm { inherit pkgs; nodejs = node; });
    npm = nodePackages."npm-^6".override { dontNpmInstall = true; };
in
pkgs.buildEnv {
    name = "build-tools-js";
    paths = [];
    postBuild = ''
        mkdir -p $out/bin/
        ln -s ${npm}/lib/node_modules/npm/bin/npm-cli.js $out/bin/npm
        ln -s ${npm}/lib/node_modules/npm/bin/npx-cli.js $out/bin/npx
        ln -s ${node}/bin/node $out/bin/node
    '';
}
