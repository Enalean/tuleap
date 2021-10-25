{ pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.stdenv.mkDerivation {
  name = "sha1collisiondetector";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/3rdparty/sha1collisiondetector.git";
    rev = "ec26bbff49d24fa94ea74d7dbf5d233622e1b579";
    sha256 = "0xmzn25a7f95c6n8g4h5pl5s678f1a126ycgx8y1899alg33d6lp";
  };

  buildInputs = [ pkgs.glibc.static ];
  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    CC="gcc -static"
    rpmbuild \
        --define "_sourcedir $(pwd)" \
        --define "_rpmdir $(pwd)" \
        --dbpath="$(pwd)"/rpmdb \
        --define "%_topdir $(pwd)" \
        --define "%_tmppath %{_topdir}/TMP" \
        --define "_rpmdir $(pwd)/RPMS" \
        --define "%_datadir /usr/share" \
        --define "%_bindir /usr/bin" \
        -bb sha1collisiondetector.spec
  '';

  installPhase = ''
    mkdir $out/
    mv RPMS/x86_64/*.rpm $out/
  '';

  dontFixUp = true;
}
