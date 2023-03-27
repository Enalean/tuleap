{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:
# Pull temporarily python3-pygments RPM into Tuleap repository until it reaches BaseOS or EPEL repo
# See https://git.rockylinux.org/staging/rpms/python-pygments/-/tree/r9
let
  rpm = pkgs.fetchurl {
    urls = [
      "https://dl.rockylinux.org/pub/rocky/9/CRB/x86_64/os/Packages/p/python3-pygments-2.7.4-4.el9.noarch.rpm"
      "https://web.archive.org/web/20230327141633/https://dl.rockylinux.org/pub/rocky/9/CRB/x86_64/os/Packages/p/python3-pygments-2.7.4-4.el9.noarch.rpm"
    ];
    sha256 = "sha256-eVhMb9sX1IKbl4wdf9tV1IthSseOxEUUEGPWqkUW4eg=";
  };
in pkgs.stdenvNoCC.mkDerivation {
  name = "python3-pygments-rpm-crb";
  dontUnpack = true;
  dontPatch = true;
  dontConfigure = true;
  dontBuild = true;
  dontFixup = true;
  installPhase = ''
    mkdir -p $out/el9/
    cp ${rpm} $out/el9/$(stripHash "${rpm}")
  '';
}
