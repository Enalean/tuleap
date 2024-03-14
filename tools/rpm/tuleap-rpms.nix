{ pkgs ? (import ../utils/nix/pinned-nixpkgs.nix) { }
, tuleapSourceTarballPath
, tuleapOS
, tuleapRelease
, withExperimental
}:

let
  tuleapVersion = pkgs.lib.strings.fileContents ../../VERSION;
  tuleapDist = if tuleapOS == "centos7" then "el7" else tuleapOS;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-rpms";

  src = [ tuleapSourceTarballPath ];

  nativeBuildInputs = [ pkgs.rpm ];

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
