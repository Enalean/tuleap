{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-KFjgm4gjig/mXlx9LLzypX0pLtFWJKeZMlLWUW6LBk8=";

  env.CGO_ENABLED=0;
}
