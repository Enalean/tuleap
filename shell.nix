{ pkgs ? (import ./tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShellNoCC {
    buildInputs = [
        (import ./tools/utils/nix/dev-tools { inherit pkgs; })
    ];

    # Use the SSH client provided by the system (FHS only) to avoid issues with Fedora/RedHat default settings
    GIT_SSH = if pkgs.lib.pathExists "/usr/bin/ssh" then "/usr/bin/ssh" else "ssh";
    # Explicitly target x86_64 when using macOS with a M1
    DOCKER_DEFAULT_PLATFORM = if (pkgs.stdenvNoCC.hostPlatform.isDarwin && pkgs.stdenvNoCC.hostPlatform.isAarch64) then "linux/amd64" else "";
}
