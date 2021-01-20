{ pkgs }:

let
    phpBase = pkgs.php73.withExtensions ({ all, ... }: []);
in phpBase
