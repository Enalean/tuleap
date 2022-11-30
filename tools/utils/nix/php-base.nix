{ pkgs }:

let
    pkgsWithoutJITSEAllocEnabled = import (fetchTarball {
      url = "https://github.com/NixOS/nixpkgs/archive/1f05f18f28eb8f9b20fc538a3450c183ffe94b77.tar.gz";
      sha256 = "1qb7yifx46yzhdvdj9np90m3gf0pifkhldbns1vnhfpllw7gr399";
    } ) { };
    phpBase = pkgsWithoutJITSEAllocEnabled.php80.withExtensions ({ all, ... }: []);
in phpBase
