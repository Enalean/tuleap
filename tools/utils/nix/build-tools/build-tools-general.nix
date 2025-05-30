{ pkgs }:

[
  pkgs.coreutils
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
  pkgs.cacert
  pkgs.trufflehog
] ++ pkgs.lib.optionals (! pkgs.stdenv.isDarwin) [ pkgs.glibc ]
