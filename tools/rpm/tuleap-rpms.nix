{ pkgs ? (import ../utils/nix/pinned-nixpkgs.nix) { }
, tuleapSourceTarballPath
, tuleapOS
, tuleapRelease
, withExperimental
}:

let
  tuleapVersion = pkgs.lib.strings.fileContents ../../VERSION;
  tuleapDist = tuleapOS;
  pkgsPinForRPM418 = import (builtins.fetchTarball {
    url = "https://github.com/NixOS/nixpkgs/archive/5083ec887760adfe12af64830a66807423a859a7.tar.gz";
    sha256 = "0sr45csfh2ff8w7jpnkkgl22aa89sza4jlhs6wq0368dpmklsl8g";
  }) { };
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-rpms";

  src = [ tuleapSourceTarballPath ];

  nativeBuildInputs = [ pkgsPinForRPM418.rpm ];

  dontUnpack = true;
  dontConfigure = true;
  dontFixup = true;

  buildPhase = ''
    set -x
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "tuleap_release ${tuleapRelease}" \
      --define "dist .${tuleapDist}" \
      --define "_sourcedir $src" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "%_buildroot %{_tmppath}/%{name}-root" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_sysconfdir /etc" \
      ${pkgs.lib.optionalString (builtins.pathExists ../../ENTERPRISE_BUILD) "--with enterprise"} \
      ${pkgs.lib.optionalString (withExperimental == "1") "--with experimental"} \
      -bb ${./tuleap.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';
}
