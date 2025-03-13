{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-H6kwjVnOaYTF+vLBov5WrgK0DfGca3Rhr2f3h2lrgbk=";

  env.CGO_ENABLED=0;
}
