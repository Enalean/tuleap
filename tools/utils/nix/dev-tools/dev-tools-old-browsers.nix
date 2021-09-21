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
        nixpkgsRev = "7138a338b58713e0dea22ddab6a6785abec7376a";
        commandToLaunch = "${browserName} -no-remote -new-instance -profile ";
    })
    (startOldBrowserScript rec {
        browserName = "chromium";
        browserPackageName = browserName;
        nixpkgsRev = "7138a338b58713e0dea22ddab6a6785abec7376a";
        commandToLaunch = "${browserName} --user-data-dir=";
    })
]
