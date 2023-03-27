{
  pkgs ? (import ../../tools/utils/nix/pinned-nixpkgs.nix) {}
}:

pkgs.stdenvNoCC.mkDerivation {
  name = "mailman-tuleap";
  src = pkgs.fetchgit {
    url = "https://tuleap.net/plugins/git/tuleap/deps/tuleap/rhel/6/mailman-tuleap.git";
    rev = "bf28be3851b03060a9213b3106656548cea70de3";
    sha256 = "sha256-t4EVoTXrbEUYaGxmRLR6SZvnSiVBWSEHK/hQKqYPT58=";
    fetchLFS = true;
  };

  dontConfigure = true;
  dontBuild = true;

  installPhase = ''
    mkdir -p $out/centos7/
    mv RPMs/*.rpm $out/centos7/
  '';

  dontFixUp = true;
}
