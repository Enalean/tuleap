{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-53TJELZGOWf0dgRdBqwkI5esiqDmyteI6rZzwrdxVz8=";

  env.CGO_ENABLED=0;
}
