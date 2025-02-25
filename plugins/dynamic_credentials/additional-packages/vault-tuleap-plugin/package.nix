{
  pkgs ? (import ../../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-tuleap-plugin";

  src = ./.;

  vendorHash = "sha256-pq4XMdPWVDoe5ZalX9r7HAs7Nwuw5RMhA7S2eUT70jw=";

  env.CGO_ENABLED=0;
}
