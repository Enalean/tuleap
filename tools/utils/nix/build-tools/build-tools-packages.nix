{ pkgs }:

[
    pkgs.file # Needed by pkgs.rpm, needs a fix in nixpkgs
    pkgs.diffutils # Needed by pkgs.rpm, needs a fix in nixpkgs
    pkgs.nix
    pkgs.perl
]
# RPM 4.18.0 cannot be built on macOS https://github.com/rpm-software-management/rpm/issues/2222
++ pkgs.lib.optionals (! pkgs.stdenv.isDarwin) [ pkgs.rpm ]
