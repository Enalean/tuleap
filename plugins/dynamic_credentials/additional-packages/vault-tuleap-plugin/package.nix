{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoLatestModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-fTmvJyGyLCIWNvJmosmG0qVhnmcYpEIh3d+A9WmSLX8=";

  env.CGO_ENABLED = 0;
}
