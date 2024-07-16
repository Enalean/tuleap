{ pkgs, phpBase ? pkgs.callPackage ../php-base.nix { } }:

let
  php = phpBase.withExtensions ({ enabled, all }: with all; enabled ++ [
    pcov
  ]);
in php
