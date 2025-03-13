{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGoModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = "sha256-0hwx2tddf2CE6cQJVIo0kWi8DffxiBmvTYAdAyNn81I=";

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
