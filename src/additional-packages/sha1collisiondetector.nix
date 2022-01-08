{ pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

pkgs.stdenv.mkDerivation {
  name = "sha1collisiondetector";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/3rdparty/sha1collisiondetector.git";
    rev = "66dc87cf8ad2bc60c1c5be383996b79f003eb218";
    sha256 = "13l56m9xp5g8flwwbx8dm070mknm1522srzd16a2h6cwnbnr2pz6";
  };

  buildInputs = [ pkgs.glibc.static ];
  nativeBuildInputs = [ pkgs.rpm pkgs.file ];

  dontConfigure = true;

  buildPhase = ''
    CC="gcc -static"
    rpmbuild \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "_binary_payload w9.xzdio" \
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
