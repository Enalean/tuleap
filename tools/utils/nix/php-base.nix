{ pkgs }:

let
    phpBase = pkgs.php74.withExtensions ({ all, ... }: []);
in phpBase
