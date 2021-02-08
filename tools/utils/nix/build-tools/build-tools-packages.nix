{ pkgs }:

[
    pkgs.rpm
    pkgs.file # Needed by pkgs.rpm, needs a fix in nixpkgs
    pkgs.diffutils # Needed by pkgs.rpm, needs a fix in nixpkgs
]
