{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

# Pull temporarily ViewVC 1.1.30 RPM into Tuleap repository until it reaches EPEL7 repo
# See https://bodhi.fedoraproject.org/updates/FEDORA-EPEL-2023-96ef72f1b2
let
  rpm = pkgs.fetchurl {
    url = "https://kojipkgs.fedoraproject.org//packages/viewvc/1.1.30/1.el7/noarch/viewvc-1.1.30-1.el7.noarch.rpm";
    sha256 = "sha256-+jnsETKXw7oECI+NWsw03ftY0Q2WmixW06jTkGMXW8U=";
  };
in pkgs.stdenvNoCC.mkDerivation {
  name = "viewvc-epel7-package";

  dontUnpack = true;
  dontPatch = true;
  dontConfigure = true;
  dontBuild = true;
  dontFixup = true;

  installPhase = ''
    mkdir $out/
    cp ${rpm} $out/$(stripHash "${rpm}")
  '';
}
