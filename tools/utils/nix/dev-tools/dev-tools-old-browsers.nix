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
        browserPackageName = "firefox-esr-91";
        nixpkgsRev = "c2c0373ae7abf25b7d69b2df05d3ef8014459ea3";
        commandToLaunch = "${browserName} -no-remote -new-instance -profile ";
    })
    (startOldBrowserScript rec {
        browserName = "chromium";
        browserPackageName = browserName;
        nixpkgsRev = "f597e7e9fcf37d8ed14a12835ede0a7d362314bd";
        commandToLaunch = "${browserName} --user-data-dir=";
    })
]
