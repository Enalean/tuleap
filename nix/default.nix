{ pkgs ? (import ./pinned-nixpkgs.nix) {} }:

let
    node = pkgs.nodejs-16_x;
    nodePackages = (import ./pnpm { inherit pkgs; nodejs = node; });
    pnpm = nodePackages."pnpm-^6".override { dontNpmInstall = true; };
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
    pnpmCli = pkgs.stdenvNoCC.mkDerivation {
        name = "pnpm-cli";
        unpackPhase = "true";
        installPhase = ''
            runHook preInstall
            mkdir -p $out/bin/
            ln -s ${pnpm}/lib/node_modules/pnpm/bin/pnpm.cjs $out/bin/pnpm
            runHook postInstall
        '';
    };
in
pkgs.buildEnv {
    name = "tuleap-realtime-dev-tools";
    paths = [
        nodeBaseBin
        pnpmCli
        pkgs.gnused
        pkgs.glibc
    ];
}

