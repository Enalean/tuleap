{ pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenvNoCC.mkDerivation {
  name = "tuleap-community-release";
  srcs = [
    ../../tools/rpm/tuleap-community-release.spec
    ../../tools/rpm/RPM-GPG-KEY-Tuleap
    ../../tools/rpm/tuleap.repo
    ../../VERSION
  ];

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  sourceRoot = ".";

  unpackPhase = ''
    for srcFile in $srcs; do
      cp -a $srcFile $(stripHash $srcFile)
    done
  '';

  buildPhase = ''
      rpmbuild \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMs" \
        --define "VERSION $(cat VERSION)" \
        -bb tuleap-community-release.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMs/*.rpm $out/
  '';

  dontFixUp = true;
}
