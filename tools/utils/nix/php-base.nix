{ pkgs }:

let
    phpBase = pkgs.php80.withExtensions ({ all, ... }: []);
in phpBase
