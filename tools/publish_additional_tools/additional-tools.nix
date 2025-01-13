{ pkgs ? (import ../utils/nix/pinned-nixpkgs.nix) {} }:

let
  importPath = path: import path { inherit pkgs; };
in pkgs.symlinkJoin {
  name = "rpm-additional-packages";
  paths = builtins.map importPath [
    ../../plugins/dynamic_credentials/additional-packages/vault-tuleap-plugin/package.nix
    ../utils/vault-gpg-plugin-rpm-compat/package.nix
    ../utils/pre-receive-tuleap-git-repo-validation/package.nix
  ];
}
