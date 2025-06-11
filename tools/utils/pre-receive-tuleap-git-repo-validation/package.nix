{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGoModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  goSum = ./src/go.sum;
  vendorHash = "sha256-Stbm8Lgxb3I0ZwTPcVSSQN318HKC2zrIN+zz4/dxDiA=";

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
