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
        browserPackageName = "firefox-esr-68";
        nixpkgsRev = "383075f38b94b25ff30ec68a1bb19b35dc9ce4e8";
        commandToLaunch = "${browserName} -no-remote -new-instance -profile ";
    })
    (startOldBrowserScript rec {
        browserName = "chromium";
        browserPackageName = browserName;
        nixpkgsRev = "3633b3271dfc644acc63b062feb4f95e88054c42";
        commandToLaunch = "${browserName} --user-data-dir=";
    })
]
