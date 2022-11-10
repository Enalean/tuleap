{ pkgs }:

[
    pkgs.gnugrep
    pkgs.gnused
    pkgs.gnumake
    pkgs.findutils
    pkgs.gitMinimal
    pkgs.libxslt
    pkgs.gettext
    pkgs.bash
    pkgs.coreutils
    pkgs.which
    pkgs.cosign
    pkgs.cachix
] ++ pkgs.lib.optionals (! pkgs.stdenv.isDarwin) [ pkgs.glibc ]
