{ pkgs }:

let
    phpBase = pkgs.php82.withExtensions ({ all, ... }: []);
in phpBase
