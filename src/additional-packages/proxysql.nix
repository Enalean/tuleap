{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:
let
  version = "3.0.6";
  packagePatchVersion = "1";
  rpmEl9 = pkgs.fetchurl {
    url = "https://github.com/sysown/proxysql/releases/download/v${version}/proxysql-${version}-${packagePatchVersion}-almalinux9.x86_64.rpm";
    hash = "sha256-w3kz18oPPUBiAitvBbyGf3d8GjkTvx6ZEsJ07oE1LJg=";
  };
  rpmEl10 = pkgs.fetchurl {
    url = "https://github.com/sysown/proxysql/releases/download/v${version}/proxysql-${version}-${packagePatchVersion}-almalinux10.x86_64.rpm";
    hash = "sha256-e0m4+eWKcISWDAH7X6p3aCjrPFsv4ycVwOuf8fz7+t4=";
  };
in
pkgs.stdenvNoCC.mkDerivation {
  name = "proxysql";
  version = "${version}-${packagePatchVersion}";
  dontUnpack = true;
  dontPatch = true;
  dontConfigure = true;
  dontBuild = true;
  dontFixup = true;
  installPhase = ''
    mkdir -p $out/el9/
    cp ${rpmEl9} $out/el9/proxysql-${version}-${packagePatchVersion}-el9.x86_64.rpm
    mkdir -p $out/el10/
    cp ${rpmEl10} $out/el10/proxysql-${version}-${packagePatchVersion}-el10.x86_64.rpm
  '';
}
