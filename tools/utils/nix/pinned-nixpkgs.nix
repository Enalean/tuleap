{}:

let
    pinnedNixpkgs = import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/04d5f1e3a87d413181af5d6dd68568228addf1c3.tar.gz") {};
in pinnedNixpkgs
