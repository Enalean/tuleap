{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGo124Module {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = "sha256-YCvMHWJTmud8+Z30aIaztFUWK/xeGaIrwTfq8cATP9E=";

  preBuild = ''
    export GOARCH="wasm"
    export GOOS="wasip1"
    export CGO_ENABLED=0
  '';

  postBuild = ''
    unset GOARCH
    unset GOOS
  '';
}
