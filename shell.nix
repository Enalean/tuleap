{ pkgs ? (import ./tools/utils/nix/pinned-nixpkgs.nix) {} }:

pkgs.mkShell {
    buildInputs = [
        (import ./tools/utils/nix/dev-tools { inherit pkgs; })
    ];

    # Use the SSH client provided by the system (FHS only) to avoid issues with Fedora/RedHat default settings
    GIT_SSH = if pkgs.lib.pathExists "/usr/bin/ssh" then "/usr/bin/ssh" else "ssh";
}
