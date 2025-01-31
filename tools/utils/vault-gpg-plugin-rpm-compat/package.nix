{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

pkgs.buildGoModule {
  name = "vault-gpg-plugin";

  src = pkgs.fetchFromGitHub {
    owner = "LeSuisse";
    repo = "vault-gpg-plugin";
    rev = "v0.6.3";
    hash = "sha256-105AcFJ1hgNtvoQuJ2JQ9PRB1s72KqjrwPSIiOKqjQ4=";
  };

  patches = [
    ./fix-rollback-version-ProtonMail-go-crypto.patch
  ];

  vendorHash = "sha256-uOWHKVw4IfdWnPoJnD/f69XaPwUj2wSF7P3vIYpMJis=";

  env.CGO_ENABLED=0;
}
