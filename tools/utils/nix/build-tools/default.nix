{ pkgs ? (import ../pinned-nixpkgs.nix) {}, phpBase ? (import ../php-base.nix { inherit pkgs; }) }:

let
  php = import ./build-tools-php.nix { inherit pkgs phpBase; };
in pkgs.mkShellNoCC {
  buildInputs = (import ./build-tools-general.nix { inherit pkgs; }) ++
    (import ./build-tools-packages.nix { inherit pkgs; }) ++
    (php) ++
    [(import ./build-tools-js.nix { inherit pkgs; })];

  # Enable Console Do Not Track / disable telemetry
  DO_NOT_TRACK = 1;
  STORYBOOK_DISABLE_TELEMETRY = 1;

  # Disable require(esm) as it currently causes a cycle in our build system
  NODE_OPTIONS = "--no-experimental-require-module";

  shellHook = ''
    export PATH="$PATH:$(pwd)/node_modules/.bin"
  '';
}
