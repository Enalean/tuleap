{ pkgs ? (import ./pinned-nixpkgs.nix) { } }:

let
  # Update those to match whatever @vercel/pkg is currently using (do not forget to update the hash as well)
  pkgFetchVersion = "3.4";
  nodeVersion = "18.5.0";
in pkgs.fetchurl {
    url = "https://github.com/vercel/pkg-fetch/releases/download/v${pkgFetchVersion}/node-v${nodeVersion}-linuxstatic-x64";
    downloadToTemp = true;
    recursiveHash = true;
    hash = "sha256-LEb5dAY4tOdNo21IR+dD+mrl20MptPs9cQEa1wo/ECs=";
    postFetch = ''
      install -D $downloadedFile $out/fetched-v${nodeVersion}-linuxstatic-x64
    '';
}
