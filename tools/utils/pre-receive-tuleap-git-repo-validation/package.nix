{ pkgs ? (import ../nix/pinned-nixpkgs.nix) { } }:

pkgs.buildGoModule {
  name = "pre-receive-tuleap-git-repo-validation";
  src = ./src;

  vendorHash = "sha256-4x1Glfwjm5XK+nDyp87Dhq44Sd1gdnGqlyUOXmYWd+g=";

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
