{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {}
}:

pkgs.stdenvNoCC.mkDerivation {
  name = "cvs-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/cvs-tuleap.git";
    rev = "74c47ca90bf754bc32d807d26d71d9748a325c1f";
    sha256 = "sha256-flBqtzbGP5T3D1Zuu85pGbLwJsuEz9ohVdth4c80M4Y=";
    fetchLFS = true;
  };

  dontConfigure = true;
  dontBuild = true;

  installPhase = ''
    mkdir $out/
    mv RPMs/*.rpm $out/
  '';

  dontFixUp = true;
}
