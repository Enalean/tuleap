{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGo124Module {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = "sha256-1Myq4uF2S8SwRhCQdtJSY2TesSFRQZAwyusPvdejDbs=";

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
