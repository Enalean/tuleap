{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGoModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = null;

  preBuild = ''
    export GOARCH="wasm"
    export GOOS="wasip1"
  '';

  postBuild = ''
    unset GOARCH
    unset GOOS
  '';
}
