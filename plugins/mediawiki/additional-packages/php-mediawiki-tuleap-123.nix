{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenvNoCC.mkDerivation {
  name = "php-mediawiki-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/php-mediawiki-tuleap.git";
    rev = "221a1338a76aef123ecee09c4f39b9479a29583c";
    sha256 = "1y112086j0dgxy8i04pxg45vyjb2ibv9ji5459s47xak7n560inz";
  };

  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    mkdir -p SOURCES
    tar -czf SOURCES/mediawiki-1.23.9.tar.gz mediawiki-1.23.9
    rpmbuild --dbpath="$(pwd)"/rpmdb --define "%_topdir $(pwd)" --define "%_tmppath %{_topdir}/TMP" --define "%_datadir /usr/share" -bb php-mediawiki-tuleap.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/noarch/*.rpm $out/
  '';

  dontFixUp = true;
}
