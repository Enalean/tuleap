# syntax = ghcr.io/akihirosuda/buildkit-nix:v0.0.1@sha256:97205438b67bc0b3a4ae5e9c38f610723bd81102bcbf215ca833cf68e99978e0

{ pkgs ? (import ./pinned-nixpkgs.nix) {}, baseContents ? (import ./default.nix { inherit pkgs; } ) }:

pkgs.dockerTools.buildImage {
  name = "tuleap-realtime-tests";
  extraCommands = "mkdir -m 0777 tmp";
  contents = [ baseContents pkgs.bash pkgs.coreutils ];
}
