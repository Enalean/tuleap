{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenv.mkDerivation {
  name = "mediawiki-math-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/mediawiki-math-tuleap.git";
    rev = "729d88339858b94844a52556cfe9d3b4c5a0b7ff";
    sha256 = "19lvymjaw5wjsijpv3c8k0gg3kvnkqmb1mqjbml3x1hp6rwgbdnz";
  };

  buildInputs = [ pkgs.glibc.static ];
  nativeBuildInputs = [ pkgs.rpm pkgs.file pkgs.ocaml ];

  dontConfigure = true;

  buildPhase = ''
    rpmbuild --nodeps \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_datadir /usr/share" \
        -bb mediawiki-math-tuleap.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}