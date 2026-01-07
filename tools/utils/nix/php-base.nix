{ pkgs }:

let
  phpBase = pkgs.php84.withExtensions ({ ... }: [ ]);
in
phpBase
