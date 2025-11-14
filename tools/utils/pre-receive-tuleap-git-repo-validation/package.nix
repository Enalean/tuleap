{
  pkgs ? (import ../nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoLatestModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  goSum = ./src/go.sum;
  vendorHash = "sha256-u5LoQnbN4zN8mCme7iRTNYZ/7WPc4ubgafhfV07Uq24=";

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
