{ pkgs ? (import ./tools/utils/nix/pinned-nixpkgs.nix) {} }:

let
  devShell = import ./tools/utils/nix/dev-tools { inherit pkgs; };
in devShell.overrideAttrs {
  # Use the SSH client provided by the system (FHS only) to avoid issues with Fedora/RedHat default settings
  GIT_SSH = if pkgs.lib.pathExists "/usr/bin/ssh" then "/usr/bin/ssh" else "ssh";
  # Explicitly target linux/amd64, our base image are linux/amd64 only at this time and not specifying this does not
  # play well when using Apple silicon
  DOCKER_DEFAULT_PLATFORM = "linux/amd64";
}
