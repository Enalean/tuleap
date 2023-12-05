{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {}
}:

let
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-realtime-rpm-package";
  srcs = [
    ./tuleap-realtime/dist/tuleap-realtime.js
    ./tuleap-realtime/packaging/tuleap-realtime.systemd-service
  ];

  nativeBuildInputs = [ pkgs.rpm pkgs.jq ];

  dontConfigure = true;

  unpackPhase = ''
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
  '';

  buildPhase = ''
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_bindir /usr/bin" \
      --define "%_unitdir /usr/lib/systemd/system" \
      --define "%_sysconfdir /etc" \
      -bb ${./tuleap-realtime/packaging/tuleap-realtime.spec}
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
