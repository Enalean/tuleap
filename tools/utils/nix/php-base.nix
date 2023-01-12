{ pkgs }:

let
    phpBase = pkgs.php81.withExtensions ({ all, ... }: []);
in phpBase
