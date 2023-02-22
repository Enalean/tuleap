{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {},
  fetchgitlfs ? (import ../../tools/utils/nix/fetchers/fetchgitlfs.nix) { fetchgit = pkgs.fetchgit; git-lfs = pkgs.git-lfs; }
}:

pkgs.stdenvNoCC.mkDerivation {
  name = "cvs-tuleap";
  src = fetchgitlfs {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/cvs-tuleap.git";
    rev = "74c47ca90bf754bc32d807d26d71d9748a325c1f";
    sha256 = "11ik6k7y2qfvalhxmkw4rckg1chrd77bnvjn1zvr8gy66svnll3y";
  };

  dontConfigure = true;
  dontBuild = true;

  installPhase = ''
    mkdir $out/
    mv RPMs/*.rpm $out/
  '';

  dontFixUp = true;
}
