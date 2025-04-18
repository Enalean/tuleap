{ pkgs ? import <nixpkgs> {} }:

let
  startOldBrowserScript = { browserName, browserPackageName, nixpkgsRev, commandToLaunch }:
    pkgs.writeScriptBin "old-${browserName}" ''
      #! /usr/bin/env nix-shell
      #! nix-shell -I nixpkgs=https://github.com/NixOS/nixpkgs/archive/${nixpkgsRev}.tar.gz -i bash -p ${browserPackageName}
      temp_profile="$(mktemp -d)"
      function cleanup {
          rm -rf "$temp_profile"
      }
      trap cleanup EXIT

      ${commandToLaunch}"$temp_profile"
    '';
in
[
  (startOldBrowserScript rec {
    browserName = "firefox";
    browserPackageName = "firefox-esr";
    nixpkgsRev = "199169a2135e6b864a888e89a2ace345703c025d";
    commandToLaunch = "${browserName}-esr -no-remote -new-instance -profile ";
  })
  (startOldBrowserScript rec {
    browserName = "chromium";
    browserPackageName = browserName;
    nixpkgsRev = "5fd95c7be6eee20988f8e4208154ad4ed33660d1";
    commandToLaunch = "${browserName} --user-data-dir=";
  })
]
