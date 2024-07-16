{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; },
}:

let
  name = "viewvc-theme-tuleap";
  src = ./viewvc-theme-tuleap;
  tuleapVersion = builtins.readFile ../../VERSION;
in pkgs.stdenvNoCC.mkDerivation {
  inherit name src;

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    tar cfz ${name}.tar.gz src
    rpmbuild \
      --define "tuleap_version ${tuleapVersion}" \
      --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
      --define "_binary_payload w16T8.zstdio" \
      --define "_sourcedir $(pwd)" \
      --define "_rpmdir $(pwd)" \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "_rpmdir $(pwd)/RPMS" \
      --define "%_datadir /usr/share" \
      -bb ${name}.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
