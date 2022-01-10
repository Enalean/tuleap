{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenvNoCC.mkDerivation {
  name = "php-mediawiki-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/php-mediawiki-tuleap.git";
    rev = "c72dc2b55e0f56d278a997c6c5588a8ab1dac00d";
    sha256 = "sha256-/nzcDAaL9iONRV5DCzzrq+gz/a4lthVu9mqSRFTqbRA=";
  };

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    mkdir -p SOURCES
    tar -czf SOURCES/mediawiki-1.23.9.tar.gz mediawiki-1.23.9
    rpmbuild \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "_binary_payload w9.xzdio" \
      --define "%_tmppath %{_topdir}/TMP" \
      --define "%_datadir /usr/share" \
      -bb php-mediawiki-tuleap.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
