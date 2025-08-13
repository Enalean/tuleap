{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:
# Pull temporarily python3-pygments RPM into Tuleap repository until it reaches BaseOS or EPEL repo
# See https://git.rockylinux.org/staging/rpms/python-pygments/-/tree/r9
let
  rpmEl9 = pkgs.fetchurl {
    urls = [
      "https://dl.rockylinux.org/pub/rocky/9/CRB/x86_64/os/Packages/p/python3-pygments-2.7.4-4.el9.noarch.rpm"
      "https://web.archive.org/web/20230327141633/https://dl.rockylinux.org/pub/rocky/9/CRB/x86_64/os/Packages/p/python3-pygments-2.7.4-4.el9.noarch.rpm"
    ];
    hash = "sha256-eVhMb9sX1IKbl4wdf9tV1IthSseOxEUUEGPWqkUW4eg=";
  };
  rpmEl10 = pkgs.fetchurl {
    urls = [
      "https://dl.rockylinux.org/pub/rocky/10/CRB/x86_64/os/Packages/p/python3-pygments-2.18.0-1.el10.noarch.rpm"
      "https://web.archive.org/web/20250812111441/https://dl.rockylinux.org/pub/rocky/10/CRB/x86_64/os/Packages/p/python3-pygments-2.18.0-1.el10.noarch.rpm"
    ];
    hash = "sha256-B9B+mSmQrVbwp+RuZlAEihmZa8su86sDAkUoAPRZd1g=";
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
    cp ${rpmEl9} $out/el9/$(stripHash "${rpmEl9}")
    mkdir -p $out/el10/
    cp ${rpmEl10} $out/el10/$(stripHash "${rpmEl10}")
  '';
}
