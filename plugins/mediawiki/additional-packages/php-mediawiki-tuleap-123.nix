{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenvNoCC.mkDerivation {
  name = "php-mediawiki-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/php-mediawiki-tuleap.git";
    rev = "e220e7b53400bc8b12b1b5fe15ff63bf09d946a7";
    sha256 = "sha256-MQULpio5cfsqbT5zLqNC0ReX8EjA+Xsh6vgeA/xXjqw=";
  };

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    mkdir -p SOURCES
    tar -czf SOURCES/mediawiki-1.23.9.tar.gz mediawiki-1.23.9
    rpmbuild \
      --dbpath="$(pwd)"/rpmdb \
      --define "%_topdir $(pwd)" \
      --define "_binary_payload w16T8.zstdio" \
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
