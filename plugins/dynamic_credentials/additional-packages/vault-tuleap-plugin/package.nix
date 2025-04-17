{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-4nmzb+7qd/4GutiAGH2bu84H7aHQmDaQu1WF0+D+UTU=";

  env.CGO_ENABLED=0;
}
