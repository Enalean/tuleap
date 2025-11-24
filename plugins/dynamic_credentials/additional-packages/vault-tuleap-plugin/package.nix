{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoLatestModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-C/X3b+4EQz7PuLqG0CRWKD0w/zTNmRJfWkFJUObQ99c=";

  env.CGO_ENABLED = 0;
}
