{
  pkgs,
  pinFile ? ./nixpkgs-pin.json,
}:

let
  nixpkgsJson = builtins.fromJSON (builtins.readFile pinFile);
  transformToEpoch = pkgs.runCommandLocal "nixpkgs-pin-epoch" { } ''
    date -d '${nixpkgsJson.date}' +%s > $out
  '';
in
pkgs.lib.fileContents transformToEpoch
