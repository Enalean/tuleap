{ pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) {}, nixpkgsPinEpoch ? (import ../../../tools/utils/nix/nixpkgs-pin-epoch.nix) { inherit pkgs; } }:

pkgs.stdenv.mkDerivation {
  name = "mediawiki-math-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/mediawiki-math-tuleap.git";
    rev = "b4a2ddae9673530398de382411828b6009b2ecc5";
    sha256 = "08q8mrhznf0brnl282dar06s0nhgjapm87z955h7l0hvq86i2vvf";
  };

  buildInputs = [ pkgs.glibc.static ];
  nativeBuildInputs = [ pkgs.rpm pkgs.file pkgs.ocaml ];

  dontConfigure = true;

  buildPhase = ''
    rpmbuild --nodeps \
        --define "nixpkgs_epoch .${nixpkgsPinEpoch}" \
        --define "_binary_payload w9.xzdio" \
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