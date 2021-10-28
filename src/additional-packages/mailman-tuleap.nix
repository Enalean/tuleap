{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  fetchgitlfs ? (import ../../tools/utils/nix/fetchers/fetchgitlfs.nix) { fetchgit = pkgs.fetchgit; git-lfs = pkgs.git-lfs; }
}:

pkgs.stdenvNoCC.mkDerivation {
  name = "mailman-tuleap";
  src = fetchgitlfs {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/mailman-tuleap.git";
    rev = "bf28be3851b03060a9213b3106656548cea70de3";
    sha256 = "17sg1yk2ll7q5c3j2na14m5fg6s9gas48rkcd0c4av7b6nhib0dp";
  };

  dontConfigure = true;
  dontBuild = true;

  installPhase = ''
    mkdir $out/
    mv RPMs/*.rpm $out/
  '';

  dontFixUp = true;
}
