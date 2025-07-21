{ pkgs }:

let
  phpBase = pkgs.php84.withExtensions ({ all, ... }: []);
in phpBase
