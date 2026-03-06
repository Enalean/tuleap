{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:
let
  version = "3.0.5";
  packagePatchVersion = "1";
  rpmEl9 = pkgs.fetchurl {
    url = "https://github.com/sysown/proxysql/releases/download/v${version}/proxysql-${version}-${packagePatchVersion}-almalinux9.x86_64.rpm";
    hash = "sha256-aDlT+zTDUO9d10A3LWWSnxLq/tsZTbY5ZJp6skUwsJ8=";
  };
  rpmEl10 = pkgs.fetchurl {
    url = "https://github.com/sysown/proxysql/releases/download/v${version}/proxysql-${version}-${packagePatchVersion}-almalinux10.x86_64.rpm";
    hash = "sha256-Y4odzRlf2yDBJ9c04Ht1XtLAAy4PfGY8UESi/wn8h0U=";
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
