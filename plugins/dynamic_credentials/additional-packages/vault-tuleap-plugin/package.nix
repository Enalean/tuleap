{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-VFX33cOMDYrUurwRimKOJJnu39NmLMY5QHiqm0BqycY=";

  env.CGO_ENABLED=0;
}
