{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGoModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = "sha256-v6ahbpQ+yCotMdoNm6kKP1YwRifqzKPo9xb/43bds5M=";

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
